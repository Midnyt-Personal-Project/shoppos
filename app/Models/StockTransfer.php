<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransfer extends Model
{
    protected $fillable = [
        'product_id', 'from_branch_id', 'to_branch_id',
        'user_id', 'quantity', 'notes',
    ];

    protected $casts = ['quantity' => 'float'];

    public function product(): BelongsTo    { return $this->belongsTo(Product::class); }
    public function fromBranch(): BelongsTo { return $this->belongsTo(Branch::class, 'from_branch_id'); }
    public function toBranch(): BelongsTo   { return $this->belongsTo(Branch::class, 'to_branch_id'); }
    public function user(): BelongsTo       { return $this->belongsTo(User::class); }
}