<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateHistory extends Model
{
    protected $fillable = [
        'version_checked',
        'new_version',
        'status',
        'message',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function status()
    {
        return $this->status;
    }
    public function message()
    {
        return $this->message;
    }
    
}
