<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'department',
        'reason',
        'notes',
        'include_in_costing',
        'unit_price',
        'total_cost',
        'user_id',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'include_in_costing' => 'boolean',
            'unit_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCostAttribute(): float
    {
        if ($this->type !== 'Out') {
            return 0.0;
        }

        if ($this->include_in_costing !== null) {
            return $this->include_in_costing ? (float) $this->total_cost : 0.0;
        }

        if (! $this->product?->include_in_costing) {
            return 0.0;
        }

        return (float) $this->quantity * (float) $this->product->price;
    }
}
