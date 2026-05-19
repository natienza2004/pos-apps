<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $userId = auth()->id();
        $ownedProduct = fn ($query) => $query->where('user_id', $userId);

        $products = Product::query()->where('user_id', $userId)->orderBy('name')->get();
        $today = Carbon::today();
        $latestStockOutDate = StockMovement::query()
            ->whereHas('product', $ownedProduct)
            ->where('type', 'Out')
            ->max('created_at');
        $defaultTrendEnd = $latestStockOutDate
            ? Carbon::parse($latestStockOutDate)->max($today)
            : $today;
        $selectedMonth = (int) $request->input('chart_month', $defaultTrendEnd->month);
        $selectedYear = (int) $request->input('chart_year', $defaultTrendEnd->year);

        if ($request->filled('chart_month') && $request->filled('chart_year')) {
            $monthDate = Carbon::create($selectedYear, $selectedMonth, 1);
            $trendStart = $monthDate->copy()->startOfMonth();
            $trendEnd = $monthDate->copy()->endOfMonth();
        } else {
            $trendEnd = Carbon::parse($request->input('chart_to', $defaultTrendEnd->toDateString()));
            $trendStart = Carbon::parse($request->input('chart_from', $trendEnd->copy()->subDays(6)->toDateString()));
        }

        if ($trendStart->gt($trendEnd)) {
            [$trendStart, $trendEnd] = [$trendEnd, $trendStart];
        }

        $dayCount = min($trendStart->diffInDays($trendEnd), 60);

        $todaysMovements = StockMovement::query()
            ->with('product')
            ->whereHas('product', $ownedProduct)
            ->where('type', 'Out')
            ->whereDate('created_at', $today)
            ->get();

        $trendMovements = StockMovement::query()
            ->with('product')
            ->whereHas('product', $ownedProduct)
            ->where('type', 'Out')
            ->whereBetween('created_at', [$trendStart->copy()->startOfDay(), $trendEnd->copy()->endOfDay()])
            ->get();

        $trendLabels = collect(range(0, $dayCount))->map(fn (int $index): string => $trendStart->copy()->addDays($index)->format('m/d'));
        $trendCosts = collect(range(0, $dayCount))->map(function (int $index) use ($trendStart, $trendMovements): float {
            $date = $trendStart->copy()->addDays($index)->toDateString();

            return $trendMovements
                ->filter(fn (StockMovement $movement): bool => $movement->created_at->toDateString() === $date)
                ->sum(fn (StockMovement $movement): float => $movement->cost);
        });
        $trendKitchenCosts = collect(range(0, $dayCount))->map(function (int $index) use ($trendStart, $trendMovements): float {
            $date = $trendStart->copy()->addDays($index)->toDateString();

            return $trendMovements
                ->filter(fn (StockMovement $movement): bool => $movement->created_at->toDateString() === $date && $movement->department === 'Kitchen')
                ->sum(fn (StockMovement $movement): float => $movement->cost);
        });
        $trendBakeryCosts = collect(range(0, $dayCount))->map(function (int $index) use ($trendStart, $trendMovements): float {
            $date = $trendStart->copy()->addDays($index)->toDateString();

            return $trendMovements
                ->filter(fn (StockMovement $movement): bool => $movement->created_at->toDateString() === $date && $movement->department === 'Bakery')
                ->sum(fn (StockMovement $movement): float => $movement->cost);
        });
        $filteredTotalCost = $trendMovements->sum(fn (StockMovement $movement): float => $movement->cost);
        $filteredKitchenCost = $trendMovements->where('department', 'Kitchen')->sum(fn (StockMovement $movement): float => $movement->cost);
        $filteredBakeryCost = $trendMovements->where('department', 'Bakery')->sum(fn (StockMovement $movement): float => $movement->cost);

        $stockAlerts = $products
            ->filter(fn (Product $product): bool => in_array($product->status, ['Low', 'Out'], true))
            ->sortBy([
                fn (Product $product): int => $product->status === 'Out' ? 0 : 1,
                fn (Product $product): float => (float) $product->current_stock,
                fn (Product $product): string => $product->name,
            ])
            ->values();

        return view('dashboard', [
            'totalInventoryValue' => $products->sum(fn (Product $product): float => $product->inventory_value),
            'lowStockCount' => $stockAlerts->count(),
            'stockAlerts' => $stockAlerts,
            'todaysConsumedCost' => $todaysMovements->sum(fn (StockMovement $movement): float => $movement->cost),
            'todayKitchenCost' => $todaysMovements->where('department', 'Kitchen')->sum(fn (StockMovement $movement): float => $movement->cost),
            'todayBakeryCost' => $todaysMovements->where('department', 'Bakery')->sum(fn (StockMovement $movement): float => $movement->cost),
            'filteredKitchenCost' => $filteredKitchenCost,
            'filteredBakeryCost' => $filteredBakeryCost,
            'trendLabels' => $trendLabels,
            'trendCosts' => $trendCosts,
            'trendKitchenCosts' => $trendKitchenCosts,
            'trendBakeryCosts' => $trendBakeryCosts,
            'chartFrom' => $trendStart,
            'chartTo' => $trendEnd,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'availableYears' => range($today->copy()->subYears(3)->year, $today->copy()->addYears(1)->year),
            'filteredTotalCost' => $filteredTotalCost,
            'recentMovements' => StockMovement::query()->with(['product', 'user'])->whereHas('product', $ownedProduct)->latest()->limit(8)->get(),
            'statusCards' => $products->sortBy('current_stock')->take(8),
        ]);
    }
}
