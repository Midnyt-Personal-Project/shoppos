<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'product_name',
        'price', 'cost', 'quantity', 'discount', 'total',
        'is_returned', 'returned_quantity',
    ];

    protected $casts = [
        'price'             => 'float',
        'cost'              => 'float',
        'quantity'          => 'float',
        'discount'          => 'float',
        'total'             => 'float',
        'returned_quantity' => 'float',
        'is_returned'       => 'boolean',
    ];

    public function sale(): BelongsTo    { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function profit(): float
    {
        return ($this->price - $this->cost) * $this->quantity;
    }
}