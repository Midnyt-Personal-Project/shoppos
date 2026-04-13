<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ActivityLog, Branch, BranchStock, Product, StockTransfer};

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;

        // Admins/owners can see all products globally; others see their branch
        $query = Product::forShop($user->shop_id)
            ->with([
                // Load ALL branch stocks so we can show full branch breakdown
                'stocks.branch',
            ]);

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        // Filter: only show products stocked in my branch
        if (!$user->isAdmin() || $request->branch_filter === 'mine') {
            $query->whereHas('stocks', fn($q) => $q->where('branch_id', $branchId));
        }

        $products   = $query->orderBy('name')->paginate(20)->withQueryString();
        $categories = Product::forShop($user->shop_id)->distinct()->pluck('category')->filter()->sort()->values();
        $branches   = Branch::where('shop_id', $user->shop_id)->where('is_active', true)->get();

        return view('products.index', compact('products', 'categories', 'branches', 'branchId'));
    }

    public function create()
    {
        $user     = auth()->user();
        $branches = Branch::where('shop_id', $user->shop_id)->where('is_active', true)->get();

        return view('products.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'barcode'     => 'nullable|string|max:100',
            'sku'         => 'nullable|string|max:100',
            'category'    => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'cost'        => 'required|numeric|min:0',
            'unit'        => 'required|string|max:50',
            'is_active'   => 'boolean',
            // Branch stock rows: branch_stocks[branch_id] = { qty, low_stock_alert }
            'branch_stocks'                  => 'required|array|min:1',
            'branch_stocks.*.branch_id'      => 'required|exists:branches,id',
            'branch_stocks.*.quantity'       => 'required|numeric|min:0',
            'branch_stocks.*.low_stock_alert'=> 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create([
                'shop_id'     => $user->shop_id,
                'name'        => $data['name'],
                'barcode'     => $data['barcode'] ?? null,
                'sku'         => $data['sku']     ?? null,
                'category'    => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
                'price'       => $data['price'],
                'cost'        => $data['cost'],
                'unit'        => $data['unit'],
                'is_active'   => $request->boolean('is_active', true),
            ]);

            foreach ($data['branch_stocks'] as $bs) {
                // Only allow branches in this shop
                $branch = Branch::where('id', $bs['branch_id'])
                                ->where('shop_id', $user->shop_id)
                                ->firstOrFail();

                BranchStock::updateOrCreate(
                    ['branch_id' => $branch->id, 'product_id' => $product->id],
                    ['quantity' => $bs['quantity'], 'low_stock_alert' => $bs['low_stock_alert']]
                );
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

        // Load all branch stocks for this product
        $stocksByBranch = BranchStock::where('product_id', $product->id)
            ->with('branch')
            ->get()
            ->keyBy('branch_id');

        return view('products.edit', compact('product', 'branches', 'stocksByBranch'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeProduct($product);
        $user = auth()->user();

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'barcode'     => 'nullable|string|max:100',
            'sku'         => 'nullable|string|max:100',
            'category'    => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'cost'        => 'required|numeric|min:0',
            'unit'        => 'required|string|max:50',
            'is_active'   => 'boolean',
            'branch_stocks'                   => 'required|array|min:1',
            'branch_stocks.*.branch_id'       => 'required|exists:branches,id',
            'branch_stocks.*.quantity'        => 'required|numeric|min:0',
            'branch_stocks.*.low_stock_alert' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $product->update([
                'name'        => $data['name'],
                'barcode'     => $data['barcode'] ?? null,
                'sku'         => $data['sku']     ?? null,
                'category'    => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
                'price'       => $data['price'],
                'cost'        => $data['cost'],
                'unit'        => $data['unit'],
                'is_active'   => $request->boolean('is_active', true),
            ]);

            foreach ($data['branch_stocks'] as $bs) {
                $branch = Branch::where('id', $bs['branch_id'])
                                ->where('shop_id', $user->shop_id)
                                ->firstOrFail();

                BranchStock::updateOrCreate(
                    ['branch_id' => $branch->id, 'product_id' => $product->id],
                    ['quantity' => $bs['quantity'], 'low_stock_alert' => $bs['low_stock_alert']]
                );
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

    /** Add stock to a specific branch */
    public function restock(Request $request, Product $product)
    {
        $this->authorizeProduct($product);
        $user = auth()->user();

        $request->validate([
            'quantity'  => 'required|numeric|min:0.01',
            'branch_id' => 'required|exists:branches,id',
        ]);

        // Confirm branch belongs to this shop
        $branch = Branch::where('id', $request->branch_id)
                        ->where('shop_id', $user->shop_id)
                        ->firstOrFail();

        BranchStock::updateOrCreate(
            ['branch_id' => $branch->id, 'product_id' => $product->id],
            // Only set defaults on create; increment is done separately
        );

        BranchStock::where('branch_id', $branch->id)
                   ->where('product_id', $product->id)
                   ->increment('quantity', $request->quantity);

        ActivityLog::record('restock', [
            'product'  => $product->name,
            'branch'   => $branch->name,
            'quantity' => $request->quantity,
        ], $product);

        return response()->json([
            'success'      => true,
            'message'      => "Added {$request->quantity} units to {$branch->name}.",
            'new_quantity' => BranchStock::where('branch_id', $branch->id)
                                         ->where('product_id', $product->id)
                                         ->value('quantity'),
        ]);
    }

    /** Transfer stock between branches */
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
            BranchStock::deduct($fromBranch->id, $product->id, $request->quantity);

            // Add to destination (create if doesn't exist)
            BranchStock::updateOrCreate(
                ['branch_id' => $toBranch->id, 'product_id' => $product->id],
                ['quantity' => 0, 'low_stock_alert' => $fromStock->low_stock_alert]
            );
            BranchStock::restore($toBranch->id, $product->id, $request->quantity);

            // Log the transfer
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
                'from_new_qty' => BranchStock::where('branch_id', $fromBranch->id)->where('product_id', $product->id)->value('quantity'),
                'to_new_qty'   => BranchStock::where('branch_id', $toBranch->id)->where('product_id', $product->id)->value('quantity'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Remove a product from a branch (set stock to 0 and detach) */
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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizeProduct(Product $product): void
    {
        if ($product->shop_id !== auth()->user()->shop_id) abort(403);
    }
}