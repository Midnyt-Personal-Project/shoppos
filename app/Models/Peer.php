<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Peer extends Model
{
     protected $fillable = ['name', 'ip_address', 'is_active', 'last_seen'];
    protected $casts = [
        'is_active' => 'boolean',
        'last_seen' => 'datetime',
    ];
}
