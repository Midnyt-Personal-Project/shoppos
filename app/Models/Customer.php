<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'shop_id', 'name', 'phone', 'email', 'address',
        'credit_limit', 'outstanding_balance',
    ];

    protected $casts = [
        'credit_limit'       => 'float',
        'outstanding_balance' => 'float',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function totalSpent(): float
    {
        return $this->sales()->where('status', 'completed')->sum('total');
    }

    public function addDebt(float $amount): void
    {
        $this->increment('outstanding_balance', $amount);
    }

    public function reduceDebt(float $amount): void
    {
        $this->decrement('outstanding_balance', $amount);
    }
}