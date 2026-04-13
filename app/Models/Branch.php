<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = ['shop_id', 'name', 'address', 'phone', 'is_active'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function stockFor(Product $product): ?BranchStock
    {
        return $this->stocks()->where('product_id', $product->id)->first();
    }

    public function todayRevenue(): float
    {
        return $this->sales()
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total');
    }

    public function todayProfit(): float
    {
        $sales = $this->sales()
            ->with('items')
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->get();

        $revenue = $sales->sum('total');
        $cost    = $sales->flatMap->items->sum(fn($i) => $i->cost * $i->quantity);

        return $revenue - $cost;
    }
}