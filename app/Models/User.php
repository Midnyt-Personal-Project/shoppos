<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'shop_id', 'branch_id', 'name', 'email', 'phone',
        'role', 'password', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime', 'is_active' => 'boolean'];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // ─── Role helpers ─────────────────────────────────────────────────────────

    public function isOwner(): bool   { return $this->role === 'owner'; }
    public function isAdmin(): bool   { return in_array($this->role, ['owner', 'admin']); }
    public function isManager(): bool { return in_array($this->role, ['owner', 'admin', 'manager']); }

    public function hasPermission(string $ability): bool
    {
        return match ($ability) {
            'manage-products'  => $this->isManager(),
            'manage-users'     => $this->isAdmin(),
            'manage-branches'  => $this->isAdmin(),
            'view-reports'     => $this->isManager(),
            'manage-expenses'  => $this->isManager(),
            'process-refunds'  => $this->isManager(),
            default            => true,
        };
    }
}