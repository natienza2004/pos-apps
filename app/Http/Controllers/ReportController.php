<?php

namespace App\Http\Controllers;

use App\Models\InventoryRecord;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function activeInventory(): View
    {
        $products = Product::query()->orderBy('category')->orderBy('name')->get();

        return view('reports.active', compact('products'));
    }

    public function daily(Request $request): View
    {
        $date = Carbon::parse($request->input('date', today()->toDateString()));
        InventoryRecord::rebuildForDate($date);

        return view('reports.daily', [
            'date' => $date,
            'previousDate' => $date->copy()->subDay(),
            'nextDate' => $date->copy()->addDay(),
            'records' => InventoryRecord::query()->with('product')->whereDate('inventory_date', $date)->get()->sortBy('product.name'),
        ]);
    }

    public function monthly(Request $request): View
    {
        $month = Carbon::parse($request->input('month', today()->format('Y-m')).'-01');
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
            'products' => Product::query()->orderBy('name')->get(),
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

        return view('reports.costing', compact('month', 'totalCost', 'kitchenCost', 'bakeryCost', 'dailyCosts', 'topIngredients'));
    }

    public function history(Request $request): View
    {
        $query = StockMovement::query()->with(['product', 'user'])->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($inner) use ($search): void {
                $inner->where('reason', 'like', "%{$search}%")
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
            'movements' => $query->paginate(20)->withQueryString(),
            'products' => Product::query()->orderBy('name')->get(),
            'departments' => StockMovement::query()->select('department')->distinct()->orderBy('department')->pluck('department'),
            'filters' => $request->only(['search', 'date_from', 'date_to', 'product_id', 'type', 'department']),
        ]);
    }
}
