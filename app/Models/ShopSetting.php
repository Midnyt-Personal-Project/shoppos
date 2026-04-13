<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ShopSetting extends Model
{
    protected $table    = 'settings';
    protected $fillable = ['shop_id', 'branch_id', 'key', 'value', 'type'];

    public function shop(): BelongsTo   { return $this->belongsTo(Shop::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }

    // ── Core get/set ──────────────────────────────────────────────────────────

    /**
     * Get a shop-level setting value (branch_id = null).
     */
    public static function get(int $shopId, string $key, mixed $default = null): mixed
    {
        $row = static::where('shop_id', $shopId)
                     ->whereNull('branch_id')
                     ->where('key', $key)
                     ->first();

        if (!$row) return $default;

        return match ($row->type) {
            'boolean' => filter_var($row->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $row->value,
            'json'    => json_decode($row->value, true),
            default   => $row->value,
        };
    }

    /**
     * Set a shop-level setting (upsert).
     */
    public static function set(int $shopId, string $key, mixed $value, string $type = 'string'): void
    {
        $stored = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json'    => json_encode($value),
            default   => (string) $value,
        };

        static::updateOrCreate(
            ['shop_id' => $shopId, 'branch_id' => null, 'key' => $key],
            ['value' => $stored, 'type' => $type]
        );
    }

    /**
     * Get a branch-level setting value.
     */
    public static function getBranch(int $shopId, int $branchId, string $key, mixed $default = null): mixed
    {
        $row = static::where('shop_id', $shopId)
                     ->where('branch_id', $branchId)
                     ->where('key', $key)
                     ->first();

        if (!$row) return $default;

        return match ($row->type) {
            'boolean' => filter_var($row->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $row->value,
            'json'    => json_decode($row->value, true),
            default   => $row->value,
        };
    }

    /**
     * Set a branch-level setting (upsert).
     */
    public static function setBranch(int $shopId, int $branchId, string $key, mixed $value, string $type = 'string'): void
    {
        $stored = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json'    => json_encode($value),
            default   => (string) $value,
        };

        static::updateOrCreate(
            ['shop_id' => $shopId, 'branch_id' => $branchId, 'key' => $key],
            ['value' => $stored, 'type' => $type]
        );
    }

    // ── Typed convenience methods ─────────────────────────────────────────────

    /** Get the Gmail SMTP config for a specific branch */
    public static function branchMailConfig(int $shopId, int $branchId): array
    {
        return [
            'gmail_address'  => static::getBranch($shopId, $branchId, 'mail_gmail_address'),
            'gmail_password' => static::getBranch($shopId, $branchId, 'mail_gmail_app_password'),
            'from_name'      => static::getBranch($shopId, $branchId, 'mail_from_name'),
            'enabled'        => (bool) static::getBranch($shopId, $branchId, 'mail_enabled', false),
        ];
    }

    /** Get all branches' mail configs for a shop (keyed by branch_id) */
    public static function allBranchMailConfigs(int $shopId): array
    {
        $rows = static::where('shop_id', $shopId)
                      ->whereNotNull('branch_id')
                      ->whereIn('key', ['mail_gmail_address', 'mail_gmail_app_password', 'mail_from_name', 'mail_enabled'])
                      ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->branch_id][$row->key] = $row->type === 'boolean'
                ? filter_var($row->value, FILTER_VALIDATE_BOOLEAN)
                : $row->value;
        }

        return $result;
    }

    /** Notification toggles (shop-level) */
    public static function notificationsFor(int $shopId): array
    {
        return [
            'notify_low_stock'     => (bool) static::get($shopId, 'notify_low_stock',     true),
            'notify_new_sale'      => (bool) static::get($shopId, 'notify_new_sale',      false),
            'notify_daily_summary' => (bool) static::get($shopId, 'notify_daily_summary', true),
            'notify_debt_reminder' => (bool) static::get($shopId, 'notify_debt_reminder', false),
        ];
    }
}