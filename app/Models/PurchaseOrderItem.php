<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'product_id', 'product_name',
        'quantity_requested', 'quantity_received', 'unit_cost',
        'status', 'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'float',
        'quantity_received'  => 'float',
        'unit_cost'          => 'float',
    ];

    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function product(): BelongsTo       { return $this->belongsTo(Product::class); }

    public function lineTotal(): float
    {
        return $this->unit_cost * $this->quantity_requested;
    }

    public function itemStatusClass(): string
    {
        return match ($this->status) {
            'received' => 'bg-green-500/10 text-green-400',
            'partial'  => 'bg-amber-500/10 text-amber-400',
            'missing'  => 'bg-red-500/10 text-red-400',
            default    => 'bg-slate-700 text-slate-400',
        };
    }
}