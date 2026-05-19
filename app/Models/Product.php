<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'sku',
        'name',
        'category',
        'unit',
        'price',
        'current_stock',
        'starting_stock',
        'low_stock_threshold',
        'include_in_costing',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'current_stock' => 'decimal:3',
            'starting_stock' => 'decimal:3',
            'include_in_costing' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function inventoryRecords(): HasMany
    {
        return $this->hasMany(InventoryRecord::class);
    }

    public function getStatusAttribute(): string
    {
        if ((float) $this->current_stock <= 0) {
            return 'Out';
        }

        return (float) $this->current_stock <= (float) $this->low_stock_threshold ? 'Low' : 'In Stock';
    }

    public function getInventoryValueAttribute(): float
    {
        return (float) $this->current_stock * (float) $this->price;
    }

    public function stockOnDate(CarbonInterface $date): float
    {
        return $this->movements()
            ->where('created_at', '<=', $date->copy()->endOfDay())
            ->get()
            ->reduce(
                fn (float $carry, StockMovement $movement): float => $carry + ($movement->type === 'In' ? (float) $movement->quantity : -1 * (float) $movement->quantity),
                (float) $this->starting_stock
            );
    }

    public function syncCurrentStock(): void
    {
        $this->update(['current_stock' => max(0, $this->stockOnDate(now()))]);
    }
}
