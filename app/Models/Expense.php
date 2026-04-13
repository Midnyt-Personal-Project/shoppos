<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'branch_id', 'user_id', 'title', 'category', 'amount', 'notes', 'expense_date',
    ];

    protected $casts = ['amount' => 'float', 'expense_date' => 'date'];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
}