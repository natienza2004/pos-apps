<?php

namespace App\Http\Controllers;

use App\Models\InventoryRecord;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function activeInventory(Request $request): View
    {
        $allProducts = Product::query()->orderBy('category')->orderBy('name')->get();
        $products = Product::query()->orderBy('category')->orderBy('name')->paginate(10);

        return view('reports.active', compact('products', 'allProducts'));
    }

    public function daily(Request $request): View
    {
        $date = Carbon::parse($request->input('date', today()->toDateString()));
        InventoryRecord::rebuildForDate($date);

        $records = InventoryRecord::query()
            ->with('product')
            ->whereDate('inventory_date', $date)
            ->get()
            ->sortBy('product.name')
            ->values();

        return view('reports.daily', [
            'date' => $date,
            'previousDate' => $date->copy()->subDay(),
            'nextDate' => $date->copy()->addDay(),
            'editing' => $request->boolean('edit'),
            'allRecords' => $records,
            'records' => $this->paginateCollection($records, 10, $request),
        ]);
    }

    public function updateDaily(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'rows' => ['required', 'array'],
            'rows.*.stock_in' => ['required', 'numeric', 'min:0'],
            'rows.*.kitchen_out' => ['required', 'numeric', 'min:0'],
            'rows.*.bakery_out' => ['required', 'numeric', 'min:0'],
        ]);

        $date = Carbon::parse($data['date']);

        if ($date->isFuture()) {
            return back()->withErrors(['date' => 'You cannot edit stock-out values for future dates.']);
        }

        InventoryRecord::rebuildForDate($date);
        $records = InventoryRecord::query()
            ->whereDate('inventory_date', $date)
            ->get()
            ->keyBy('product_id');

        try {
            DB::transaction(function () use ($data, $date, $records): void {
                foreach ($data['rows'] as $productId => $row) {
                    $product = Product::query()->lockForUpdate()->findOrFail($productId);
                    $record = $records->get($productId);

                    if (! $record) {
                        continue;
                    }

                    $stockInDelta = round((float) $row['stock_in'] - (float) $record->stock_in, 3);
                    $kitchenDelta = round((float) $row['kitchen_out'] - (float) $record->kitchen_out, 3);
                    $bakeryDelta = round((float) $row['bakery_out'] - (float) $record->bakery_out, 3);
                    $availableOnDate = $product->stockOnDate($date) + $stockInDelta;
                    $outDelta = $kitchenDelta + $bakeryDelta;

                    if ($outDelta > $availableOnDate) {
                        throw new \RuntimeException("{$product->name} does not have enough available stock on {$date->format('M d, Y')}.");
                    }

                    $this->createSheetMovement($product, 'In', $stockInDelta, 'Supplier', 'Daily sheet edit: Stock In adjusted', $date);
                    $this->createSheetMovement($product, 'Out', $kitchenDelta, 'Kitchen', 'Daily sheet edit: Kitchen Out adjusted', $date);
                    $this->createSheetMovement($product, 'Out', $bakeryDelta, 'Bakery', 'Daily sheet edit: Bakery Out adjusted', $date);
                    $product->syncCurrentStock();
                }
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['rows' => $exception->getMessage()]);
        }

        InventoryRecord::rebuildForDate($date);

        return redirect()
            ->route('reports.daily', ['date' => $date->toDateString()])
            ->with('success', 'Daily sheet changes saved.');
    }

    public function exportDaily(Request $request): StreamedResponse
    {
        $date = Carbon::parse($request->input('date', today()->toDateString()));
        InventoryRecord::rebuildForDate($date);

        $records = InventoryRecord::query()
            ->with('product')
            ->whereDate('inventory_date', $date)
            ->get()
            ->sortBy('product.name');

        $filename = 'daily-reconciliation-'.$date->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($records, $date): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Daily Reconciliation', $date->format('Y-m-d')]);
            fputcsv($file, []);
            fputcsv($file, [
                'Product Name',
                'SKU',
                'Unit',
                'Unit Price',
                'Start Stock',
                'Stock In',
                'Kitchen Out',
                'Bakery Out',
                'Total Out',
                'Ending Stock',
                'Kitchen Cost',
                'Bakery Cost',
                'Total Cost',
                'In Costing',
                'Status',
            ]);

            foreach ($records as $record) {
                $totalOut = (float) $record->kitchen_out + (float) $record->bakery_out;

                fputcsv($file, [
                    $record->product->name,
                    $record->product->sku,
                    $record->product->unit,
                    number_format((float) $record->product->price, 2, '.', ''),
                    $this->formatQuantity((float) $record->starting_stock),
                    $this->formatQuantity((float) $record->stock_in),
                    $this->formatQuantity((float) $record->kitchen_out),
                    $this->formatQuantity((float) $record->bakery_out),
                    $this->formatQuantity($totalOut),
                    $this->formatQuantity((float) $record->ending_stock),
                    number_format((float) $record->kitchen_cost, 2, '.', ''),
                    number_format((float) $record->bakery_cost, 2, '.', ''),
                    number_format((float) $record->total_cost, 2, '.', ''),
                    $record->product->include_in_costing ? 'Yes' : 'No',
                    $record->product->status,
                ]);
            }

            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function formatQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private function createSheetMovement(Product $product, string $type, float $quantity, string $department, string $reason, Carbon $date): void
    {
        if (abs($quantity) < 0.001) {
            return;
        }

        $includeInCosting = $type === 'Out' ? (bool) $product->include_in_costing : false;
        $unitPrice = (float) $product->price;

        StockMovement::query()->create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'department' => $department,
            'reason' => $reason,
            'include_in_costing' => $includeInCosting,
            'unit_price' => $unitPrice,
            'total_cost' => $type === 'Out' && $includeInCosting ? $quantity * $unitPrice : 0,
            'user_id' => $this->systemUser()->id,
            'created_at' => $date->copy()->setTimeFrom(now()),
            'updated_at' => $date->copy()->setTimeFrom(now()),
        ]);
    }

    private function systemUser(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'system@stockflow.local'],
            ['name' => 'StockFlow Operator', 'role' => 'Admin', 'password' => 'password']
        );
    }

    public function monthly(Request $request): View
    {
        $month = Carbon::parse($request->input('month', today()->format('Y-m')).'-01');
        $products = Product::query()->orderBy('name')->get();
        $movements = StockMovement::query()
            ->with('product')
            ->where('type', 'Out')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        $departmentCosts = $movements
            ->groupBy('department')
            ->map(fn ($items): float => $items->sum(fn (StockMovement $movement): float => $movement->cost));

        $dailyCosts = $movements
            ->groupBy(fn (StockMovement $movement): string => $movement->created_at->format('Y-m-d'))
            ->map(fn ($items): float => $items->sum(fn (StockMovement $movement): float => $movement->cost));

        $topIngredients = $movements
            ->groupBy('product_id')
            ->map(fn ($items): array => [
                'name' => $items->first()->product->name,
                'cost' => $items->sum(fn (StockMovement $movement): float => $movement->cost),
            ])
            ->sortByDesc('cost')
            ->take(5)
            ->values();

        return view('reports.monthly', [
            'month' => $month,
            'allProducts' => $products,
            'products' => $this->paginateCollection($products, 10, $request),
            'totalCost' => $movements->sum(fn (StockMovement $movement): float => $movement->cost),
            'departmentCosts' => $departmentCosts,
            'dailyCosts' => $dailyCosts,
            'topIngredients' => $topIngredients,
        ]);
    }

    public function costing(Request $request): View
    {
        $month = Carbon::parse($request->input('month', today()->format('Y-m')).'-01');
        $movements = StockMovement::query()
            ->with('product')
            ->where('type', 'Out')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        $totalCost = $movements->sum(fn (StockMovement $movement): float => $movement->cost);
        $kitchenCost = $movements->where('department', 'Kitchen')->sum(fn (StockMovement $movement): float => $movement->cost);
        $bakeryCost = $movements->where('department', 'Bakery')->sum(fn (StockMovement $movement): float => $movement->cost);
        $dailyCosts = $movements
            ->groupBy(fn (StockMovement $movement): string => $movement->created_at->format('d'))
            ->map(fn ($items): float => $items->sum(fn (StockMovement $movement): float => $movement->cost));
        $topIngredients = $movements
            ->groupBy('product_id')
            ->map(fn ($items): array => [
                'name' => $items->first()->product->name,
                'cost' => $items->sum(fn (StockMovement $movement): float => $movement->cost),
            ])
            ->sortByDesc('cost')
            ->take(5)
            ->values();

        $breakdown = $movements
            ->groupBy('product_id')
            ->map(function ($items) use ($totalCost): array {
                $first = $items->first();
                $cost = $items->sum(fn (StockMovement $movement): float => $movement->cost);

                return [
                    'name' => $first->product->name,
                    'sku' => $first->product->sku,
                    'unit' => $first->product->unit,
                    'quantity' => $items->sum(fn (StockMovement $movement): float => (float) $movement->quantity),
                    'kitchen_quantity' => $items->where('department', 'Kitchen')->sum(fn (StockMovement $movement): float => (float) $movement->quantity),
                    'bakery_quantity' => $items->where('department', 'Bakery')->sum(fn (StockMovement $movement): float => (float) $movement->quantity),
                    'cost' => $cost,
                    'percent' => $totalCost > 0 ? ($cost / $totalCost) * 100 : 0,
                ];
            })
            ->sortByDesc('cost')
            ->values();

        return view('reports.costing', compact('month', 'totalCost', 'kitchenCost', 'bakeryCost', 'dailyCosts', 'topIngredients', 'breakdown'));
    }

    public function previewCostingAudit(Request $request): Response
    {
        return $this->costingAuditPdf($request, false);
    }

    public function downloadCostingAudit(Request $request): Response
    {
        return $this->costingAuditPdf($request, true);
    }

    private function costingAuditPdf(Request $request, bool $download): Response
    {
        $month = Carbon::parse($request->input('month', today()->format('Y-m')).'-01');
        $movements = StockMovement::query()
            ->with('product')
            ->where('type', 'Out')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        $totalCost = $movements->sum(fn (StockMovement $movement): float => $movement->cost);
        $kitchenCost = $movements->where('department', 'Kitchen')->sum(fn (StockMovement $movement): float => $movement->cost);
        $bakeryCost = $movements->where('department', 'Bakery')->sum(fn (StockMovement $movement): float => $movement->cost);
        $lines = [
            'StockFlow Costing Audit',
            'Month: '.$month->format('F Y'),
            'Generated: '.now()->format('Y-m-d H:i'),
            '',
            'Total Monthly Expenditure: PHP '.number_format($totalCost, 2),
            'Kitchen Total: PHP '.number_format($kitchenCost, 2),
            'Bakery Total: PHP '.number_format($bakeryCost, 2),
            '',
            'Ingredient Breakdown',
        ];

        $movements
            ->groupBy('product_id')
            ->map(function ($items): array {
                $first = $items->first();

                return [
                    'name' => $first->product->name,
                    'sku' => $first->product->sku,
                    'quantity' => $items->sum(fn (StockMovement $movement): float => (float) $movement->quantity),
                    'unit' => $first->product->unit,
                    'cost' => $items->sum(fn (StockMovement $movement): float => $movement->cost),
                ];
            })
            ->sortByDesc('cost')
            ->each(function (array $row) use (&$lines): void {
                $lines[] = $row['name'].' ('.$row['sku'].') - '.$this->formatQuantity((float) $row['quantity']).' '.$row['unit'].' - PHP '.number_format($row['cost'], 2);
            });

        if ($movements->isEmpty()) {
            $lines[] = 'No costing activity for this month.';
        }

        $pdf = $this->simplePdf($lines);
        $filename = 'costing-audit-'.$month->format('Y-m').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
        ]);
    }

    private function simplePdf(array $lines): string
    {
        $content = "BT\n/F1 13 Tf\n50 790 Td\n16 TL\n";

        foreach ($lines as $line) {
            $content .= '('.$this->escapePdfText($line).") Tj\nT*\n";
        }

        $content .= "ET\n";
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($content)." >>\nstream\n".$content."endstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        for ($index = 1; $index <= count($objects); $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    public function history(Request $request): View
    {
        $query = StockMovement::query()->with(['product', 'user'])->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($inner) use ($search): void {
                $inner->where('reason', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($productQuery) => $productQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if (in_array($request->input('type'), ['In', 'Out'], true)) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        return view('reports.history', [
            'movements' => $query->paginate(10)->withQueryString(),
            'products' => Product::query()->orderBy('name')->get(),
            'departments' => StockMovement::query()->select('department')->distinct()->orderBy('department')->pluck('department'),
            'filters' => $request->only(['search', 'date_from', 'date_to', 'product_id', 'type', 'department']),
        ]);
    }

    private function paginateCollection($items, int $perPage, Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
