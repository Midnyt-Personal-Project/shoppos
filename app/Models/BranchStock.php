<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchStock extends Model
{
    protected $fillable = ['branch_id', 'product_id', 'quantity', 'low_stock_alert'];

    protected $casts = ['quantity' => 'float', 'low_stock_alert' => 'float'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isLow(): bool
    {
        return $this->quantity <= $this->low_stock_alert;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    public static function deduct(int $branchId, int $productId, float $qty): void
    {
        static::where('branch_id', $branchId)
              ->where('product_id', $productId)
              ->decrement('quantity', $qty);
    }

    public static function restore(int $branchId, int $productId, float $qty): void
    {
        static::where('branch_id', $branchId)
              ->where('product_id', $productId)
              ->increment('quantity', $qty);
    }
}