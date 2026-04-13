<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'plan_name',
        'plan_slug',
        'status',
        'activated_at',
        'expires_at',
        'verified_at',
        'verification_token',
        'days_remaining',
        'shop_domain',
        'shop_name',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at'   => 'datetime',
        'verified_at'  => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(LicensePlan::class, 'plan_slug', 'slug');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function daysRemaining(): int
    {
        if (!$this->expires_at) return 0;

        $days = now()->diffInDays($this->expires_at, false);
        return max(0, $days);
    }

    public function activate(string $shopDomain, string $shopName): void
    {
        $this->update([
            'status'       => 'active',
            'activated_at' => now(),
            'shop_domain'  => $shopDomain,
            'shop_name'    => $shopName,
        ]);
    }
}
