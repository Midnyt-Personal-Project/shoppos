<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    protected $fillable = [
        'branch_id', 'user_id', 'title', 'category', 'amount', 'notes', 'expense_date',
        'receipt_path',
    ];

    protected $casts = ['amount' => 'float', 'expense_date' => 'date'];

       // Accessor for full URL
    public function getReceiptUrlAttribute()
    {
        return $this->receipt_path ? Storage::url($this->receipt_path) : null;
    }

    // Delete the receipt file when the expense is deleted
    protected static function booted()
    {
        static::deleting(function ($expense) {
            if ($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path)) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
        });
    }
    
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
}