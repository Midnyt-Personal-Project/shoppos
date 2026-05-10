<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

use App\{Syncable, SyncableFile};

class Product extends Model


{
    use SyncableFile;
    protected $fillable = [
        'shop_id',
        'name',
        'barcode',
        'sku',
        'category',
        'branch',
        'image',
        'description',
        'price',
        'cost',
        'type',
        'unit',
        'image',
        'is_active',
        'allow_price_override',
    ];

    protected $casts = [
        'price'     => 'float',
        'cost'      => 'float',
        'type' => 'string',
        'is_active' => 'boolean',
        'allow_price_override' => 'boolean',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function isProduct(): bool
    {
        return $this->type === 'product';
    }
    public function stocks(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockInBranch(int $branchId): float
    {
        return $this->stocks()->where('branch_id', $branchId)->value('quantity') ?? 0;
    }

    public function profit(): float
    {
        return $this->price - $this->cost;
    }

    public function profitMargin(): float
    {
        if ($this->price == 0) return 0;
        return round(($this->profit() / $this->price) * 100, 2);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForShop($q, int $shopId)
    {
        return $q->where('shop_id', $shopId);
    }

    public function scopeSearch($q, string $term)
    {
        return $q->where(function ($q) use ($term) {
            $q->where('name', 'like', "%$term%")
                ->orWhere('barcode', 'like', "%$term%")
                ->orWhere('sku', 'like', "%$term%");
        });
    }
}
