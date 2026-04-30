<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncOutbox extends Model
{
    public $timestamps = false;
    protected $table = 'sync_outbox';
    protected $fillable = ['table_name', 'record_id', 'action', 'data', 'synced'];
    protected $casts = [
        'data' => 'array',
        'synced' => 'boolean',
    ];
}
