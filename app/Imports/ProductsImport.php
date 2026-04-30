<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use App\Models\{BranchStock, Product};
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow};

class ProductsImport implements ToCollection, WithHeadingRow
{
    protected $branchId;
    protected $shopId;
    protected $errors = [];
    protected $stats = [
        'added'         => 0,
        'updated'       => 0,
        'skipped'       => 0,
        'total_quantity' => 0,   // <-- NEW: sum of all stock values
        'details'       => []
    ];

    public function __construct()
    {
        $this->branchId = auth()->user()->branch_id;
        $this->shopId   = auth()->user()->shop_id;
    }

    public function getStats()
    {
        return $this->stats;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if (empty($row['name']) || empty($row['sku']) || empty($row['price']) || empty($row['cost'])) {
                $this->errors[] = "Row {$rowNumber}: Missing required fields (name, sku, price, cost)";
                $this->stats['skipped']++;
                continue;
            }

            $price = (float) $row['price'];
            $cost  = (float) $row['cost'];
            $stock = isset($row['stock']) ? (int) $row['stock'] : 0;

            // Add to total quantity (regardless of new/update)
            $this->stats['total_quantity'] += $stock;

            $existing = Product::where('shop_id', $this->shopId)
                        ->where('sku', $row['sku'])
                        ->first();

            if ($existing) {
                $oldStock = $existing->stockInBranch($this->branchId);
                $oldPrice = $existing->price;
                $oldCost  = $existing->cost;

                $existing->update([
                    'name'        => $row['name'],
                    'barcode'     => $row['barcode'] ?? null,
                    'price'       => $price,
                    'cost'        => $cost,
                    'category'    => $row['category'] ?? null,
                    'description' => $row['description'] ?? null,
                    'is_active'   => true,
                ]);

                BranchStock::updateOrCreate(
                    ['branch_id' => $this->branchId, 'product_id' => $existing->id],
                    ['quantity' => $stock]
                );

                $this->stats['updated']++;
                $this->stats['details'][] = [
                    'sku'       => $row['sku'],
                    'name'      => $row['name'],
                    'old_qty'   => $oldStock,
                    'new_qty'   => $stock,
                    'old_price' => $oldPrice,
                    'new_price' => $price,
                    'old_cost'  => $oldCost,
                    'new_cost'  => $cost,
                    'action'    => 'updated'
                ];
                continue;
            }

            // Create new product
            $product = Product::create([
                'shop_id'      => $this->shopId,
                'name'         => $row['name'],
                'sku'          => $row['sku'],
                'barcode'      => $row['barcode'] ?? null,
                'price'        => $price,
                'cost'         => $cost,
                'category'     => $row['category'] ?? null,
                'description'  => $row['description'] ?? null,
                'is_active'    => true,
            ]);

            if ($stock > 0) {
                BranchStock::create([
                    'branch_id'  => $this->branchId,
                    'product_id' => $product->id,
                    'quantity'   => $stock,
                ]);
            }

            $this->stats['added']++;
            $this->stats['details'][] = [
                'sku'       => $row['sku'],
                'name'      => $row['name'],
                'new_qty'   => $stock,
                'new_price' => $price,
                'action'    => 'added'
            ];
        }

        if (!empty($this->errors)) {
            throw ValidationException::withMessages(['file' => implode(', ', $this->errors)]);
        }
    }
}