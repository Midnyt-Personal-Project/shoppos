<?php

namespace App\Models;

use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class PurchaseOrder extends Model
{
    protected $fillable = [
        'reference', 'shop_id', 'branch_id',
        'created_by', 'approved_by',
        'supplier_name', 'supplier_phone', 'notes',
        'status', 'approved_at', 'expected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expected_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function shop(): BelongsTo     { return $this->belongsTo(Shop::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany      { return $this->hasMany(PurchaseOrderItem::class); }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function generateReference(int $branchId): string
    {
        $date  = now()->format('YmdHis');
        $count = static::whereDate('created_at', today())
                       ->where('branch_id', $branchId)
                       ->count() + 1;

        return \sprintf('PO-%s-%04d', $date, $count);
    }

    public function totalCost(): float
    {
        return $this->items->sum(fn($i) => $i->unit_cost * $i->quantity_requested);
    }

    public function totalReceived(): float
    {
        return $this->items->sum('quantity_received');
    }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return in_array($this->status, ['approved', 'partial', 'received']); }
    public function isDraft(): bool    { return $this->status === 'draft'; }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'draft'    => 'bg-slate-700 text-slate-300',
            'pending'  => 'bg-amber-500/10 text-amber-400',
            'approved' => 'bg-blue-500/10 text-blue-400',
            'rejected' => 'bg-red-500/10 text-red-400',
            'partial'  => 'bg-purple-500/10 text-purple-400',
            'received' => 'bg-green-500/10 text-green-400',
            default    => 'bg-slate-700 text-slate-300',
        };
    }

    /** Recalculate PO status based on item statuses */
    public function recalculateStatus(): void
    {
        $items    = $this->items;
        $total    = $items->count();
        $received = $items->where('status', 'received')->count();
        $partial  = $items->where('status', 'partial')->count();
        $missing  = $items->where('status', 'missing')->count();

        if ($received === $total) {
            $this->update(['status' => 'received']);
        } elseif ($received > 0 || $partial > 0 || $missing > 0) {
            $this->update(['status' => 'partial']);
        }
    }
}