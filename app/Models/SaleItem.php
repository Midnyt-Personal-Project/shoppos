<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'product_name',
        'price', 'cost', 'quantity', 'discount', 'total','tax_rate','tax_amount',
        'is_returned', 'returned_quantity',
    ];

    protected $casts = [
        'price'             => 'float',
        'cost'              => 'float',
        'quantity'          => 'float',
        'discount'          => 'float',
        'total'             => 'float',
        'tax_rate'          => 'decimal:2',
        'tax_amount'        => 'decimal:2',
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