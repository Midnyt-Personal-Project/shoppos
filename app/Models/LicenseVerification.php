<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseVerification extends Model
{
    protected $fillable = [
        'license_id',
        'ip_address',
        'domain',
        'success',
        'verified_at',
    ];

    protected $casts = [
        'success'     => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}