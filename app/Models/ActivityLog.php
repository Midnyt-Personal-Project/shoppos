<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'branch_id', 'action', 'model_type', 'model_id', 'data', 'ip_address',
    ];

    protected $casts = ['data' => 'array'];

    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }

    public static function record(string $action, array $data = [], $model = null): void
    {
        static::create([
            'user_id'    => auth()->id(),
            'branch_id'  => auth()->user()?->branch_id,
            'action'     => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id'   => $model?->id,
            'data'       => $data,
            'ip_address' => request()->ip(),
        ]);
    }
}