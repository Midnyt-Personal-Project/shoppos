<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Storage};
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Models\{ActivityLog, Branch, BranchStock, Product, StockMovement, StockTransfer};
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $branchId = current_branch()->id;

        $query = Product::forShop($user->shop_id)
            ->with(['stocks.branch', 'serviceBranches']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if (!$user->isAdmin() || $request->branch_filter === 'mine') {
            $query->where(function ($q) use ($branchId) {
                $q->where('type', 'product')
                    ->whereHas('stocks', fn($subQ) => $subQ->where('branch_id', $branchId))
                    ->orWhere(function ($q) use ($branchId) {
                        $q->where('type', 'service')
                            ->whereHas('serviceBranches', fn($subQ) => $subQ->where('branch_id', $branchId));
                    });
            });
        }

        $products   = $query->orderBy('name')->paginate(20)->withQueryString();
        $categories = Product::forShop($user->shop_id)->distinct()->pluck('category')->filter()->sort()->values();
        $branches   = Branch::where('shop_id', $user->shop_id)->where('is_active', true)->get();

        return view('products.index', compact('products', 'categories', 'branches', 'branchId'));
    }

    public function create()
    {
        $user = auth()->user();
        $shopId = $user->shop_id;

        $existingCategories = Product::where('shop_id', $shopId)->distinct()->pluck('category')->filter()->values()->toArray();
        $existingUnits      = Product::where('shop_id', $shopId)->distinct()->pluck('unit')->filter()->values()->toArray();

        $predefinedCategories = [
            'Analgesics & Pain Relief', 'Antibiotics', 'Antimalarials', 'Anthelmintics (Dewormers)',
            'Antifungals', 'Antivirals', 'Gastrointestinal Preparations', 'Respiratory Preparations',
            'Antihistamines & Allergy', 'Cardiovascular Drugs', 'Endocrine & Diabetes Drugs',
            'Neurology & Psychiatric Drugs', 'Vitamins, Minerals & Supplements',
            'Fluids & Electrolytes (Oral & IV)', 'Antiseptics & Disinfectants',
            'Medical Supplies & Consumables (Non-Drug)', 'Diagnostic Tests & Equipment',
            'Wound Care & Suturing', 'Mobility & Orthopedic Supports', 'Infection Control & Sterilization',
            'Pharmacy Accessories (Pill cutters, containers)', 'Vaccine & Cold Chain Supplies',
            'Specialty Injectables', 'Ophthalmology (Eye) Preparations', 'Otology (Ear) Preparations',
            'Dental & Oral Care', 'Nasal Preparations', 'Reproductive Health & Contraceptives',
            'Pediatric Specific Preparations', 'Nutritional & Enteral Feeds', 'Herbal & Traditional Remedies',
            'Dermatology (Skin) Preparations (excluding antifungals)', 'Cough & Cold Preparations',
            'Emergency & Resuscitation Drugs', 'Controlled Substances (Narcotics)', 'Topical Corticosteroids',
            'Muscle Relaxants'
        ];

        $predefinedUnits = [
            'Tablet', 'Capsule', 'Strip', 'Pack', 'Bottle', 'Box', 'Vial', 'Inhaler', 'Sachet', 'Tube',
            'Jar', 'Roll', 'Piece', 'Pair', 'Set', 'Kg', 'g', 'Litre', 'ml', 'Syringe', 'Pessary',
            'Suppository', 'Drop', 'Spray', 'Cream', 'Ointment', 'Gel', 'Lotion', 'Powder', 'Patch',
            'Test', 'Meter', 'Pair (gloves)', 'Mask', 'Dressing', 'Bandage', 'Tape', 'Catheter', 'Bag',
            'Thermometer', 'Monitor', 'Lancet'
        ];

        $allCategories = array_unique(array_merge($existingCategories, $predefinedCategories));
        $allUnits      = array_unique(array_merge($existingUnits, $predefinedUnits));
        sort($allCategories);
        sort($allUnits);

        $branches = Branch::where('shop_id', $shopId)->where('is_active', true)->get();

        return view('products.create', compact('branches', 'allCategories', 'allUnits'));
    }

    public function store(Request $request)
{
    $user = auth()->user();

    $rules = [
        'name'        => 'required|string|max:255',
        'barcode'     => 'nullable|string|max:100',
        'sku'         => 'nullable|string|max:100',
        'category'    => 'nullable|string|max:100',
        'description' => 'nullable|string',
        'price'       => 'required|numeric|min:0',
        'cost'        => 'required|numeric|min:0',
        'unit'        => 'required|string|max:50',
        'is_active'   => 'boolean',
        'type'        => 'required|in:product,service',
        'allow_price_override' => 'boolean',
        'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif',
    ];

    if ($request->type === 'product') {
        $rules['branch_stocks'] = 'required|array|min:1';
        $rules['branch_stocks.*.branch_id'] = 'required|exists:branches,id';
        $rules['branch_stocks.*.quantity'] = 'required|numeric|min:0';
        $rules['branch_stocks.*.low_stock_alert'] = 'required|numeric|min:0';
    } else {
        $rules['service_branches'] = 'nullable|array';
        $rules['service_branches.*'] = 'required|exists:branches,id';
    }

    $data = $request->validate($rules);

    DB::beginTransaction();
    try {
        $product = Product::create([
            'shop_id'     => $user->shop_id,
            'name'        => $data['name'],
            'barcode'     => $data['barcode'] ?? null,
            'sku'         => $data['sku'] ?? null,
            'category'    => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
            'price'       => $data['price'],
            'cost'        => $data['cost'],
            'unit'        => $data['unit'],
            'is_active'   => $request->boolean('is_active', true),
            'type'        => $data['type'],
            'allow_price_override' => $request->boolean('allow_price_override', true),
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->image = $path;
            $product->save();
        }

        if ($data['type'] === 'product') {
            foreach ($data['branch_stocks'] as $bs) {
                if ((float) $bs['quantity'] > 0) {
                    $branch = Branch::where('id', $bs['branch_id'])
                        ->where('shop_id', $user->shop_id)
                        ->firstOrFail();

                    // Create or update stock record
                    $stock = BranchStock::updateOrCreate(
                        ['branch_id' => $branch->id, 'product_id' => $product->id],
                        ['quantity' => $bs['quantity'], 'low_stock_alert' => $bs['low_stock_alert']]
                    );

                    // Log initial stock as a 'restock' movement
                    $this->logStockMovement(
                        $product,
                        $branch,
                        'manual',
                        $bs['quantity'],
                        0,                     // before quantity was 0 (new product)
                        $bs['quantity'],       // after quantity is the initial stock
                        null,
                        'Initial stock on product creation'
                    );
                }
                // If quantity = 0, do nothing – product not assigned to that branch
            }
        } else {
            if (!empty($data['service_branches'])) {
                $product->serviceBranches()->sync($data['service_branches']);
            }
        }

        ActivityLog::record('product_created', ['product' => $product->name], $product);
        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withErrors(['error' => $e->getMessage()])->withInput();
    }

    return redirect()->route('products.index')->with('success', 'Product added successfully.');
}

    public function edit(Product $product)
    {
        $this->authorizeProduct($product);
        $user     = auth()->user();
        $branches = Branch::where('shop_id', $user->shop_id)->where('is_active', true)->get();
        $stocksByBranch = BranchStock::where('product_id', $product->id)->with('branch')->get()->keyBy('branch_id');

        return view('products.edit', compact('product', 'branches', 'stocksByBranch'));
    }

 public function update(Request $request, Product $product)
{
    $this->authorizeProduct($product);
    $user = auth()->user();

    $rules = [
        'name'        => 'required|string|max:255',
        'barcode'     => 'nullable|string|max:100',
        'sku'         => 'nullable|string|max:100',
        'category'    => 'nullable|string|max:100',
        'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif',
        'description' => 'nullable|string',
        'price'       => 'required|numeric|min:0',
        'cost'        => 'required|numeric|min:0',
        'unit'        => 'required|string|max:50',
        'is_active'   => 'boolean',
        'type'        => 'required|in:product,service',
        'allow_price_override' => 'boolean',
    ];

    if ($request->type === 'product') {
        $rules['branch_stocks'] = 'required|array|min:1';
        $rules['branch_stocks.*.branch_id'] = 'required|exists:branches,id';
        $rules['branch_stocks.*.quantity'] = 'required|numeric|min:0';
        $rules['branch_stocks.*.low_stock_alert'] = 'required|numeric|min:0';
    } else {
        $rules['service_branches'] = 'nullable|array';
        $rules['service_branches.*'] = 'required|exists:branches,id';
    }

    $data = $request->validate($rules);

    DB::beginTransaction();
    try {
        // ─── 1. Update product details ──────────────────────────────────────
        $product->update([
            'name'        => $data['name'],
            'barcode'     => $data['barcode'] ?? null,
            'sku'         => $data['sku'] ?? null,
            'category'    => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
            'price'       => $data['price'],
            'cost'        => $data['cost'],
            'unit'        => $data['unit'],
            'is_active'   => $request->boolean('is_active'),
            'type'        => $data['type'],
            'allow_price_override' => $request->boolean('allow_price_override', true),
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $path = $request->file('image')->store('products', 'public');
            $product->image = $path;
            $product->save();
        }

        // ─── 2. Branch assignment handling ──────────────────────────────────
        if ($data['type'] === 'product') {
            // Fetch current stock records (with branch relation) for logging
            $oldStocks = BranchStock::where('product_id', $product->id)
                ->with('branch')
                ->get()
                ->keyBy('branch_id');

            $submittedBranchIds = collect($data['branch_stocks'])->pluck('branch_id')->toArray();

            // ─── 3. Log stock changes ──────────────────────────────────────
            // a) Log removals (branches not in the request)
            foreach ($oldStocks as $branchId => $oldStock) {
                if (!in_array($branchId, $submittedBranchIds)) {
                    $this->logStockMovement(
                        $product,
                        $oldStock->branch,
                        'adjustment_subtract',
                        -$oldStock->quantity,
                        $oldStock->quantity,
                        0,
                        null,
                        "Product updated from branch via product update"
                    );
                }
            }

            // b) Log changes for branches in the request
            foreach ($data['branch_stocks'] as $bs) {
                $branch = Branch::find($bs['branch_id']);
                $newQty = (float) $bs['quantity'];
                $oldQty = $oldStocks->has($branch->id) ? $oldStocks[$branch->id]->quantity : 0;

                if ($newQty != $oldQty) {
                    if ($oldQty == 0) {
                        // New assignment
                        $type = 'restock';
                        $sign = $newQty;
                        $notes = 'Initial stock assignment via product update';
                    } elseif ($newQty > $oldQty) {
                        $type = 'adjustment_add';
                        $sign = $newQty - $oldQty;
                        $notes = 'Stock increased via product update';
                    } else { // $newQty < $oldQty
                        $type = 'adjustment_subtract';
                        $sign = $newQty - $oldQty; // negative
                        $notes = 'Stock decreased via product update';
                    }

                    $this->logStockMovement(
                        $product,
                        $branch,
                        $type,
                        $sign,
                        $oldQty,
                        $newQty,
                        null,
                        $notes
                    );
                }
            }

            // ─── 4. Execute stock updates ──────────────────────────────────
            // Get existing branch IDs for this product
            $existingBranchIds = BranchStock::where('product_id', $product->id)->pluck('branch_id')->toArray();

            // Delete stock records for branches not present in the request
            $toDelete = array_diff($existingBranchIds, $submittedBranchIds);
            if (!empty($toDelete)) {
                BranchStock::where('product_id', $product->id)
                    ->whereIn('branch_id', $toDelete)
                    ->delete();
            }

            // Process each submitted branch stock
            foreach ($data['branch_stocks'] as $bs) {
                $branch = Branch::where('id', $bs['branch_id'])
                    ->where('shop_id', $user->shop_id)
                    ->firstOrFail();

                if ((float) $bs['quantity'] > 0) {
                    BranchStock::updateOrCreate(
                        ['branch_id' => $branch->id, 'product_id' => $product->id],
                        ['quantity' => $bs['quantity'], 'low_stock_alert' => $bs['low_stock_alert']]
                    );
                } else {
                    BranchStock::where('branch_id', $branch->id)
                        ->where('product_id', $product->id)
                        ->delete();
                }
            }
        } else {
            // Service branch assignment
            if (!empty($data['service_branches'])) {
                $product->serviceBranches()->sync($data['service_branches']);
            } else {
                $product->serviceBranches()->detach();
            }
        }

        ActivityLog::record('product_updated', ['product' => $product->name], $product);
        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withErrors(['error' => $e->getMessage()])->withInput();
    }

    return redirect()->route('products.index')->with('success', 'Product updated.');
}

    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);
        $product->update(['is_active' => false]);
        ActivityLog::record('product_deactivated', ['product' => $product->name], $product);
        return redirect()->route('products.index')->with('success', 'Product deactivated.');
    }

    /**
     * Add stock to a specific branch (restock)
     */
    public function restock(Request $request, Product $product)
    {
        $this->authorizeProduct($product);
        $user = auth()->user();

        $request->validate([
            'quantity'  => 'required|numeric|min:0.01',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branch = Branch::where('id', $request->branch_id)
            ->where('shop_id', $user->shop_id)
            ->firstOrFail();

        $stock = BranchStock::firstOrCreate(
            ['branch_id' => $branch->id, 'product_id' => $product->id],
            ['quantity' => 0, 'low_stock_alert' => 5]
        );

        $before = $stock->quantity;
        $stock->increment('quantity', $request->quantity);
        $after = $stock->fresh()->quantity;

        $this->logStockMovement(
            $product,
            $branch,
            'restock',
            $request->quantity,
            $before,
            $after,
            null,
            'Manual restock'
        );

        ActivityLog::record('restock', [
            'product'  => $product->name,
            'branch'   => $branch->name,
            'quantity' => $request->quantity,
        ], $product);

        return response()->json([
            'success'      => true,
            'message'      => "Added {$request->quantity} units to {$branch->name}.",
            'new_quantity' => $after,
        ]);
    }

    /**
     * Transfer stock between branches
     */
    public function transfer(Request $request, Product $product)
    {
        $this->authorizeProduct($product);
        $user = auth()->user();

        $request->validate([
            'from_branch_id' => 'required|exists:branches,id|different:to_branch_id',
            'to_branch_id'   => 'required|exists:branches,id',
            'quantity'       => 'required|numeric|min:0.01',
            'notes'          => 'nullable|string|max:255',
        ]);

        $fromBranch = Branch::where('id', $request->from_branch_id)->where('shop_id', $user->shop_id)->firstOrFail();
        $toBranch   = Branch::where('id', $request->to_branch_id)->where('shop_id', $user->shop_id)->firstOrFail();

        $fromStock = BranchStock::where('branch_id', $fromBranch->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$fromStock || $fromStock->quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock at {$fromBranch->name}. Available: " . ($fromStock?->quantity ?? 0),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Deduct from source
            $beforeFrom = $fromStock->quantity;
            $fromStock->decrement('quantity', $request->quantity);
            $afterFrom = $fromStock->fresh()->quantity;

            $this->logStockMovement(
                $product,
                $fromBranch,
                'transfer_out',
                -$request->quantity,
                $beforeFrom,
                $afterFrom,
                null,
                "Transferred to {$toBranch->name}"
            );

            // Add to destination
            $toStock = BranchStock::firstOrCreate(
                ['branch_id' => $toBranch->id, 'product_id' => $product->id],
                ['quantity' => 0, 'low_stock_alert' => $fromStock->low_stock_alert]
            );
            $beforeTo = $toStock->quantity;
            $toStock->increment('quantity', $request->quantity);
            $afterTo = $toStock->fresh()->quantity;

            $this->logStockMovement(
                $product,
                $toBranch,
                'transfer_in',
                $request->quantity,
                $beforeTo,
                $afterTo,
                null,
                "Received from {$fromBranch->name}"
            );

            StockTransfer::create([
                'product_id'     => $product->id,
                'from_branch_id' => $fromBranch->id,
                'to_branch_id'   => $toBranch->id,
                'user_id'        => $user->id,
                'quantity'       => $request->quantity,
                'notes'          => $request->notes,
            ]);

            ActivityLog::record('stock_transfer', [
                'product'  => $product->name,
                'from'     => $fromBranch->name,
                'to'       => $toBranch->name,
                'quantity' => $request->quantity,
            ], $product);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$request->quantity} units of {$product->name} transferred from {$fromBranch->name} to {$toBranch->name}.",
                'from_new_qty' => $afterFrom,
                'to_new_qty'   => $afterTo,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Manual stock adjustment (add or subtract)
     */
    public function adjustStock(Request $request, Product $product)
    {
        $this->authorizeProduct($product);
        $user = auth()->user();

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'quantity'  => 'required|numeric|min:0.01',
            'type'      => 'required|in:add,subtract',
            'notes'     => 'nullable|string|max:255',
        ]);

        $branch = Branch::where('id', $request->branch_id)
            ->where('shop_id', $user->shop_id)
            ->firstOrFail();

        $stock = BranchStock::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $before = $stock->quantity;
        $qty = $request->quantity;

        if ($request->type === 'subtract') {
            if ($stock->quantity < $qty) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock.'
                ], 422);
            }
            $stock->decrement('quantity', $qty);
            $type = 'adjustment_subtract';
            $sign = -$qty;
        } else {
            $stock->increment('quantity', $qty);
            $type = 'adjustment_add';
            $sign = $qty;
        }

        $after = $stock->fresh()->quantity;

        $this->logStockMovement(
            $product,
            $branch,
            $type,
            $sign,
            $before,
            $after,
            null,
            $request->notes ?? 'Manual adjustment'
        );

        ActivityLog::record('stock_adjustment', [
            'product'  => $product->name,
            'branch'   => $branch->name,
            'type'     => $request->type,
            'quantity' => $qty,
            'notes'    => $request->notes,
        ], $product);

        return response()->json([
            'success' => true,
            'message' => "Stock adjusted successfully.",
            'new_quantity' => $after,
        ]);
    }

    public function getProductBranches(Product $product)
{
    $this->authorizeProduct($product);
    $branches = BranchStock::where('product_id', $product->id)
        ->with('branch')
        ->get()
        ->map(fn($stock) => [
            'id' => $stock->branch->id,
            'name' => $stock->branch->name,
            'quantity' => $stock->quantity,
            'low_stock_alert' => $stock->low_stock_alert,
            'unit' => $product->unit,
        ]);
    return response()->json($branches);
}

    /**
     * Remove a product from a branch (delete stock record)
     */
    public function removeBranch(Request $request, Product $product)
    {
        $this->authorizeProduct($product);
        $user = auth()->user();

        $request->validate(['branch_id' => 'required|exists:branches,id']);

        Branch::where('id', $request->branch_id)->where('shop_id', $user->shop_id)->firstOrFail();

        BranchStock::where('product_id', $product->id)
            ->where('branch_id', $request->branch_id)
            ->delete();

        ActivityLog::record('product_branch_removed', [
            'product'   => $product->name,
            'branch_id' => $request->branch_id,
        ], $product);

        return response()->json(['success' => true, 'message' => 'Product removed from branch.']);
    }

    /**
     * Get stock logs for a product (with date filtering and pagination)
     */
    public function stockLogs(Request $request, Product $product)
    {
        $this->authorizeProduct($product);

        $logs = StockMovement::where('product_id', $product->id)
            ->with(['branch', 'user'])
            ->when($request->filled('from'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->from);
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->to);
            })
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($logs);
    }

    /**
     * Download import template Excel file
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['name', 'price', 'stock', 'barcode', 'sku', 'cost', 'category', 'description'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        $sheet->setCellValue('A2', 'Paracetamol 500mg');
        $sheet->setCellValue('B2', 5.99);
        $sheet->setCellValue('C2', 100);
        $sheet->setCellValue('D2', '1234567890123');
        $sheet->setCellValue('E2', 'PAR-001');
        $sheet->setCellValue('F2', 3.50);
        $sheet->setCellValue('G2', 'Analgesics');
        $sheet->setCellValue('H2', 'Effective pain relief for headaches and fever.');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="product_import_template.xlsx"');

        return $response;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizeProduct(Product $product): void
    {
        if ($product->shop_id !== auth()->user()->shop_id) abort(403);
    }

    private function logStockMovement($product, $branch, $type, $quantity, $beforeQty, $afterQty, $reference = null, $notes = null)
    {
        StockMovement::create([
            'product_id'       => $product->id,
            'branch_id'        => $branch->id,
            'user_id'          => auth()->id(),
            'type'             => $type,
            'quantity'         => $quantity,
            'before_quantity'  => $beforeQty,
            'after_quantity'   => $afterQty,
            'reference'        => $reference,
            'notes'            => $notes,
        ]);
    }
}