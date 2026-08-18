<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_agent_id', 'category_id', 'name', 'barcode', 'sku',
        'price', 'cost_price', 'stock', 'min_stock', 'unit',
        'image', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function paymentAgent(): BelongsTo
    {
        return $this->belongsTo(PaymentAgent::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function needsRestock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    // Auto-kurangi stok
    public function decreaseStock(int $qty, string $reference = null): void
    {
        $before = $this->stock;
        $this->decrement('stock', $qty);
        $this->stockMovements()->create([
            'qty' => -$qty,
            'type' => 'out',
            'reference' => $reference,
            'stock_before' => $before,
            'stock_after' => $before - $qty,
        ]);
    }

    // Auto-tambah stok (restock)
    public function increaseStock(int $qty, string $reference = null): void
    {
        $before = $this->stock;
        $this->increment('stock', $qty);
        $this->stockMovements()->create([
            'qty' => $qty,
            'type' => 'in',
            'reference' => $reference,
            'stock_before' => $before,
            'stock_after' => $before + $qty,
        ]);
    }
}
