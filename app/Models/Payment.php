<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'sale_id', 'customer_id', 'method', 'amount', 'reference', 'notes',
    ];

    protected $casts = ['amount' => 'float'];

    public function sale(): BelongsTo     { return $this->belongsTo(Sale::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'cash'         => 'Cash',
            'mobile_money' => 'Mobile Money',
            'card'         => 'Card',
            'credit'       => 'Credit',
            default        => ucfirst($this->method),
        };
    }
}
