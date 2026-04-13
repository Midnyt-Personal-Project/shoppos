<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'reference', 'branch_id', 'user_id', 'customer_id',
        'subtotal', 'discount', 'tax', 'total',
        'amount_paid', 'change', 'balance_due',
        'status', 'payment_status', 'notes',
    ];

    protected $casts = [
        'subtotal'     => 'float',
        'discount'     => 'float',
        'tax'          => 'float',
        'total'        => 'float',
        'amount_paid'  => 'float',
        'change'       => 'float',
        'balance_due'  => 'float',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany      { return $this->hasMany(SaleItem::class); }
    public function payments(): HasMany   { return $this->hasMany(Payment::class); }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public static function generateReference(int $branchId): string
    {
        $date  = now()->format('Ymd');
        $count = static::whereDate('created_at', today())
                       ->where('branch_id', $branchId)
                       ->count() + 1;

        return sprintf('SALE-%s-%04d', $date, $count);
    }

    public function profit(): float
    {
        return $this->items->sum(fn($i) => ($i->price - $i->cost) * $i->quantity);
    }

    public function isPaid(): bool    { return $this->payment_status === 'paid'; }
    public function isPartial(): bool { return $this->payment_status === 'partial'; }
}