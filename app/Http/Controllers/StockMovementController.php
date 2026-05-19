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
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        return view('movements.index', [
            'products' => Product::query()->where('user_id', $userId)->orderBy('name')->get(),
            'movements' => StockMovement::query()
                ->with(['product', 'user'])
                ->whereHas('product', fn ($query) => $query->where('user_id', $userId))
                ->latest()
                ->paginate(10),
        ]);
    }

    public function stockIn(): View
    {
        return view('movements.stock-in', [
            'products' => Product::query()->where('user_id', auth()->id())->orderBy('name')->get(),
        ]);
    }

    public function stockOut(): View
    {
        return view('movements.stock-out', [
            'products' => Product::query()->where('user_id', auth()->id())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = auth()->id();
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where(fn ($query) => $query->where('user_id', $userId))],
            'type' => ['required', 'in:In,Out'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'department' => ['required', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'movement_date' => ['required', 'date'],
            'include_in_costing' => ['nullable', 'boolean'],
        ]);

        try {
            DB::transaction(function () use ($data, $userId): void {
                $product = Product::query()->where('user_id', $userId)->lockForUpdate()->findOrFail($data['product_id']);
                $quantity = (float) $data['quantity'];
                $movementDate = Carbon::parse($data['movement_date']);

                if ($data['type'] === 'Out' && $movementDate->isToday()) {
                    $this->reconcileCurrentStockBaseline($product);
                    $product->refresh();
                }

                $availableOnDate = $product->stockOnDate($movementDate);

                if ($data['type'] === 'Out' && $movementDate->isFuture()) {
                    throw new \RuntimeException('Stock-out date cannot be in the future.');
                }

                if ($data['type'] === 'Out' && $quantity > $availableOnDate) {
                    throw new \RuntimeException('Stock-out quantity is higher than available stock for '.$movementDate->format('M d, Y').'. Available: '.$this->formatQuantity($availableOnDate).' '.$product->unit.'.');
                }

                $movementAt = $movementDate->copy()->setTimeFrom(Carbon::now());
                unset($data['movement_date']);
                $includeInCosting = array_key_exists('include_in_costing', $data)
                    ? (bool) $data['include_in_costing']
                    : (bool) $product->include_in_costing;
                unset($data['include_in_costing']);
                $unitPrice = (float) $product->price;

                StockMovement::query()->create($data + [
                    'include_in_costing' => $includeInCosting,
                    'unit_price' => $unitPrice,
                    'total_cost' => $data['type'] === 'Out' && $includeInCosting ? $quantity * $unitPrice : 0,
                    'user_id' => $userId ?? $this->systemUser()->id,
                    'created_at' => $movementAt,
                    'updated_at' => $movementAt,
                ]);

                $product->syncCurrentStock();
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()])->withInput();
        }

        InventoryRecord::rebuildForDate(Carbon::parse($request->input('movement_date')), $userId);

        return back()->with('success', $data['type'] === 'In' ? 'Stock added.' : 'Stock released.');
    }

    public function update(Request $request, StockMovement $movement): RedirectResponse
    {
        $userId = auth()->id();
        $movement->loadMissing('product');
        abort_unless($movement->product?->user_id === $userId, 404);

        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where(fn ($query) => $query->where('user_id', $userId))],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'department' => ['required', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'movement_date' => ['required', 'date'],
            'include_in_costing' => ['nullable', 'boolean'],
        ]);

        $oldProductId = $movement->product_id;
        $oldDate = $movement->created_at->copy();
        $movementDate = Carbon::parse($data['movement_date']);

        try {
            DB::transaction(function () use ($data, $movement, $movementDate, $oldProductId, $userId): void {
                $product = Product::query()->where('user_id', $userId)->lockForUpdate()->findOrFail($data['product_id']);
                if ($oldProductId !== $product->id) {
                    Product::query()->where('user_id', $userId)->lockForUpdate()->findOrFail($oldProductId);
                }

                $quantity = (float) $data['quantity'];

                if ($movement->type === 'Out' && $movementDate->isFuture()) {
                    throw new \RuntimeException('Stock-out date cannot be in the future.');
                }

                if ($movement->type === 'Out') {
                    if ($movementDate->isToday()) {
                        $this->reconcileCurrentStockBaseline($product);
                        $product->refresh();
                    }

                    $availableOnDate = $this->stockOnDateExcludingMovement($product, $movementDate, $movement);

                    if ($quantity > $availableOnDate) {
                        throw new \RuntimeException('Stock-out quantity is higher than available stock for '.$movementDate->format('M d, Y').'. Available: '.$this->formatQuantity($availableOnDate).' '.$product->unit.'.');
                    }
                }

                $includeInCosting = array_key_exists('include_in_costing', $data)
                    ? (bool) $data['include_in_costing']
                    : (bool) $product->include_in_costing;
                $unitPrice = (float) $product->price;

                $movement->update([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'department' => $data['department'],
                    'reason' => $data['reason'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'include_in_costing' => $includeInCosting,
                    'unit_price' => $unitPrice,
                    'total_cost' => $movement->type === 'Out' && $includeInCosting ? $quantity * $unitPrice : 0,
                    'created_at' => $movementDate->copy()->setTimeFrom($movement->created_at),
                ]);

                $product->syncCurrentStock();

                if ($oldProductId !== $product->id) {
                    Product::query()->where('user_id', $userId)->find($oldProductId)?->syncCurrentStock();
                }
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['movement' => $exception->getMessage()]);
        }

        InventoryRecord::rebuildForDate($oldDate, $userId);
        InventoryRecord::rebuildForDate($movementDate, $userId);

        return back()->with('success', 'Movement updated.');
    }

    private function stockOnDateExcludingMovement(Product $product, Carbon $date, StockMovement $excludedMovement): float
    {
        return $product->movements()
            ->where('stock_movements.id', '!=', $excludedMovement->id)
            ->where('created_at', '<=', $date->copy()->endOfDay())
            ->get()
            ->reduce(
                fn (float $carry, StockMovement $movement): float => $carry + ($movement->type === 'In' ? (float) $movement->quantity : -1 * (float) $movement->quantity),
                (float) $product->starting_stock
            );
    }

    private function reconcileCurrentStockBaseline(Product $product): void
    {
        $calculatedCurrent = $product->stockOnDate(now());
        $displayedCurrent = (float) $product->current_stock;
        $difference = round($displayedCurrent - $calculatedCurrent, 3);

        if (abs($difference) < 0.001) {
            return;
        }

        $product->update([
            'starting_stock' => max(0, (float) $product->starting_stock + $difference),
        ]);
    }

    private function formatQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private function systemUser(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'system@stockflow.local'],
            ['name' => 'StockFlow Operator', 'role' => 'Admin', 'password' => 'password']
        );
    }
}
