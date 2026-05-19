<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'inventory_date',
        'starting_stock',
        'stock_in',
        'kitchen_out',
        'bakery_out',
        'ending_stock',
        'kitchen_cost',
        'bakery_cost',
        'total_cost',
    ];

    protected function casts(): array
    {
        return [
            'inventory_date' => 'date',
            'starting_stock' => 'decimal:3',
            'stock_in' => 'decimal:3',
            'kitchen_out' => 'decimal:3',
            'bakery_out' => 'decimal:3',
            'ending_stock' => 'decimal:3',
            'kitchen_cost' => 'decimal:2',
            'bakery_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function rebuildForDate(CarbonInterface $date, ?string $userId = null): void
    {
        Product::query()
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->each(function (Product $product) use ($date): void {
                $before = $product->movements()
                    ->whereDate('created_at', '<', $date->toDateString())
                    ->get()
                    ->reduce(fn (float $carry, StockMovement $movement): float => $carry + ($movement->type === 'In' ? (float) $movement->quantity : -1 * (float) $movement->quantity), (float) $product->starting_stock);

                $movements = $product->movements()
                    ->whereDate('created_at', $date->toDateString())
                    ->get();

                $stockIn = $movements->where('type', 'In')->sum(fn (StockMovement $movement): float => (float) $movement->quantity);
                $kitchenOut = $movements->where('type', 'Out')->where('department', 'Kitchen')->sum(fn (StockMovement $movement): float => (float) $movement->quantity);
                $bakeryOut = $movements->where('type', 'Out')->where('department', 'Bakery')->sum(fn (StockMovement $movement): float => (float) $movement->quantity);
                $kitchenCost = $movements->where('type', 'Out')->where('department', 'Kitchen')->sum(fn (StockMovement $movement): float => $movement->cost);
                $bakeryCost = $movements->where('type', 'Out')->where('department', 'Bakery')->sum(fn (StockMovement $movement): float => $movement->cost);

                self::query()->updateOrCreate(
                    ['product_id' => $product->id, 'inventory_date' => $date->toDateString()],
                    [
                        'starting_stock' => $before,
                        'stock_in' => $stockIn,
                        'kitchen_out' => $kitchenOut,
                        'bakery_out' => $bakeryOut,
                        'ending_stock' => $before + $stockIn - $kitchenOut - $bakeryOut,
                        'kitchen_cost' => $kitchenCost,
                        'bakery_cost' => $bakeryCost,
                        'total_cost' => $kitchenCost + $bakeryCost,
                    ]
                );
            });
    }
}
