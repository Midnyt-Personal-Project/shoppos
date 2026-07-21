<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};

use App\Models\{ActivityLog, BranchStock, Product, PurchaseOrder, PurchaseOrderItem, StockMovement};

class PurchaseOrderController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = PurchaseOrder::with(['creator', 'branch', 'items'])
            ->where('shop_id', $user->shop_id);

        if (! $user->isAdmin()) {
            $query->where('branch_id', current_branch()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('search')) {
            $query->where('reference', 'like', '%' . $request->search . '%');
        }

        $orders   = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $branches = \App\Models\Branch::where('shop_id', $user->shop_id)->get();

        $pendingCount = $user->isAdmin()
            ? PurchaseOrder::where('shop_id', $user->shop_id)->where('status', 'pending')->count()
            : 0;

        return view('purchase-orders.index', compact('orders', 'branches', 'pendingCount'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create()
    {
        $user     = auth()->user();
        $products = Product::forShop($user->shop_id)
            ->active()
            ->with(['stocks' => fn($q) => $q->where('branch_id', current_branch()->id)])
            ->orderBy('name')
            ->get();
        $suppliers = $this->getSuppliers();

        return view('purchase-orders.create', compact('products', 'suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_name'            => 'nullable|string|max:255',
            'supplier_phone'           => 'nullable|string|max:30',
            'expected_at'              => 'nullable|date',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity_requested' => 'required|numeric|min:0.01',
            'items.*.unit_cost'        => 'nullable|numeric|min:0',
        ]);

        $user = auth()->user();

        DB::beginTransaction();
        try {
            $po = PurchaseOrder::create([
                'reference'      => PurchaseOrder::generateReference(current_branch()->id),
                'shop_id'        => $user->shop_id,
                'branch_id'      => current_branch()->id,
                'created_by'     => $user->id,
                'supplier_name'  => $request->supplier_name,
                'supplier_phone' => $request->supplier_phone,
                'notes'          => $request->notes,
                'expected_at'    => $request->expected_at,
                'status'         => 'pending',
            ]);

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                PurchaseOrderItem::create([
                    'purchase_order_id'  => $po->id,
                    'product_id'         => $product->id,
                    'product_name'       => $product->name,
                    'quantity_requested' => $item['quantity_requested'],
                    'unit_cost'          => $item['unit_cost'] ?? $product->cost,
                    'status'             => 'pending',
                ]);
            }

            ActivityLog::record('po_created', ['reference' => $po->reference], $po);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        return redirect()->route('purchase-orders.show', $po)
                         ->with('success', "Purchase order {$po->reference} submitted.");
    }

    protected function getSuppliers()
    {
        return PurchaseOrder::where('shop_id', auth()->user()->shop_id)
            ->whereNotNull('supplier_name')
            ->select('supplier_name', 'supplier_phone')
            ->distinct()
            ->get()
            ->map(fn($po) => [
                'name'  => $po->supplier_name,
                'phone' => $po->supplier_phone,
            ])
            ->values()
            ->toArray();
    }

    // ── Show / Detail ─────────────────────────────────────────────────────────

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorizePO($purchaseOrder);
        $purchaseOrder->load(['items.product', 'creator', 'approver', 'branch.shop']);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    // ── Approve / Reject ──────────────────────────────────────────────────────

    public function approve(PurchaseOrder $purchaseOrder)
    {
        $this->authorizePO($purchaseOrder);

        if (! auth()->user()->isAdmin()) abort(403);
        if (! $purchaseOrder->isPending()) {
            return back()->with('error', 'Only pending orders can be approved.');
        }

        $purchaseOrder->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        ActivityLog::record('po_approved', ['reference' => $purchaseOrder->reference], $purchaseOrder);

        return back()->with('success', "PO {$purchaseOrder->reference} approved.");
    }

    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizePO($purchaseOrder);

        if (! auth()->user()->isAdmin()) abort(403);

        $purchaseOrder->update([
            'status' => 'rejected',
            'notes'  => $purchaseOrder->notes . ($request->reason ? "\n\nRejected: " . $request->reason : ''),
        ]);

        ActivityLog::record('po_rejected', ['reference' => $purchaseOrder->reference], $purchaseOrder);

        return back()->with('success', "PO {$purchaseOrder->reference} rejected.");
    }

    // ── Receive items (updates stock + logs movement) ──────────────────────

    public function receiveItem(Request $request, PurchaseOrderItem $item)
    {
        $po = $item->purchaseOrder;
        $this->authorizePO($po);

        $request->validate([
            'quantity_received' => 'required|numeric|min:0',
            'item_status'       => 'required|in:received,partial,missing',
            'notes'             => 'nullable|string|max:255',
        ]);

        if (! $po->isApproved()) {
            return response()->json(['success' => false, 'message' => 'Order must be approved before receiving items.'], 422);
        }

        DB::beginTransaction();
        try {
            // Update item
            $item->update([
                'quantity_received' => $request->quantity_received,
                'status'            => $request->item_status,
                'notes'             => $request->notes,
            ]);

            // Get the branch
            $branch = \App\Models\Branch::find($po->branch_id);

            // Update stock only if quantity received > 0
            if ($request->quantity_received > 0) {
                $stock = BranchStock::firstOrCreate(
                    ['branch_id' => $po->branch_id, 'product_id' => $item->product_id],
                    ['quantity' => 0, 'low_stock_alert' => 5]
                );

                $before = $stock->quantity;
                $stock->increment('quantity', $request->quantity_received);
                $after = $stock->fresh()->quantity;

                // Log stock movement
                $this->logStockMovement(
                    $item->product,       // product model (need to fetch)
                    $branch,
                    'purchase_receive',
                    $request->quantity_received,
                    $before,
                    $after,
                    $po->reference,
                    "Received from PO {$po->reference} – " . ($request->notes ?: '')
                );
            }

            ActivityLog::record('po_item_received', ['reference' => $po->reference, 'product_id' => $item->product_id], $item);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Item received and stock updated.']);
    }

    // ── Receive ALL at once (quick receive) ─────────────────────────────────

    public function receiveAll(PurchaseOrder $purchaseOrder)
    {
        $this->authorizePO($purchaseOrder);

        if (! $purchaseOrder->isApproved()) {
            return back()->with('error', 'Order must be approved first.');
        }

        DB::beginTransaction();
        try {
            $branch = \App\Models\Branch::find($purchaseOrder->branch_id);

            foreach ($purchaseOrder->items as $item) {
                if ($item->status === 'received') continue;

                $qty = $item->quantity_requested;

                $item->update([
                    'quantity_received' => $qty,
                    'status'            => 'received',
                ]);

                // Update stock
                $stock = BranchStock::firstOrCreate(
                    ['branch_id' => $purchaseOrder->branch_id, 'product_id' => $item->product_id],
                    ['quantity' => 0, 'low_stock_alert' => 5]
                );

                $before = $stock->quantity;
                $stock->increment('quantity', $qty);
                $after = $stock->fresh()->quantity;

                // Log stock movement
                $this->logStockMovement(
                    $item->product,
                    $branch,
                    'purchase_receive',
                    $qty,
                    $before,
                    $after,
                    $purchaseOrder->reference,
                    "Bulk receive from PO {$purchaseOrder->reference}"
                );
            }

            $purchaseOrder->update(['status' => 'received']);
            ActivityLog::record('po_fully_received', ['reference' => $purchaseOrder->reference], $purchaseOrder);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'All items received and stock updated.');
    }

    // ── Print view ────────────────────────────────────────────────────────────

    public function print(PurchaseOrder $purchaseOrder)
    {
        $this->authorizePO($purchaseOrder);
        $purchaseOrder->load(['items.product', 'creator', 'approver', 'branch.shop']);

        return view('purchase-orders.print', compact('purchaseOrder'));
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorizePO($purchaseOrder);

        if (! in_array($purchaseOrder->status, ['draft', 'rejected'])) {
            return back()->with('error', 'Only draft or rejected orders can be deleted.');
        }

        $ref = $purchaseOrder->reference;
        $purchaseOrder->delete();
        ActivityLog::record('po_deleted', ['reference' => $ref]);

        return redirect()->route('purchase-orders.index')->with('success', "PO {$ref} deleted.");
    }

    // ── Edit & Update ─────────────────────────────────────────────────────────

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorizePO($purchaseOrder);
        if (!$purchaseOrder->isEditable()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                             ->with('error', 'This purchase order cannot be edited.');
        }

        $user = auth()->user();
        $purchaseOrder->load(['items.product.stocks']);
        $products = Product::forShop($user->shop_id)
            ->active()
            ->with(['stocks' => fn($q) => $q->where('branch_id', current_branch()->id)])
            ->orderBy('name')
            ->get();
        $suppliers = $this->getSuppliers();

        $items = $purchaseOrder->items->map(function ($item) {
            return [
                'id'                => $item->id,
                'product_id'        => $item->product_id,
                'product_name'      => $item->product_name,
                'current_stock'     => $item->product?->stocks->first()?->quantity ?? 0,
                'unit'              => $item->product?->unit ?? '',
                'unit_cost'         => $item->unit_cost,
                'quantity_requested'=> $item->quantity_requested,
                'delete'            => false,
            ];
        })->values()->toArray();

        return view('purchase-orders.edit', compact('purchaseOrder', 'products', 'items', 'suppliers'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizePO($purchaseOrder);
        if (!$purchaseOrder->isEditable()) {
            return back()->with('error', 'This purchase order cannot be edited.');
        }

        $request->validate([
            'supplier_name'            => 'nullable|string|max:255',
            'supplier_phone'           => 'nullable|string|max:30',
            'expected_at'              => 'nullable|date',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.id'               => 'nullable|exists:purchase_order_items,id',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity_requested' => 'required|numeric|min:0.01',
            'items.*.unit_cost'        => 'nullable|numeric|min:0',
            'items.*.delete'           => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder->update([
                'supplier_name'  => $request->supplier_name,
                'supplier_phone' => $request->supplier_phone,
                'notes'          => $request->notes,
                'expected_at'    => $request->expected_at,
            ]);

            $existingIds = [];
            foreach ($request->items as $itemData) {
                if (!empty($itemData['delete']) && !empty($itemData['id'])) {
                    PurchaseOrderItem::where('id', $itemData['id'])
                        ->where('purchase_order_id', $purchaseOrder->id)
                        ->delete();
                    continue;
                }

                if (!empty($itemData['id'])) {
                    $item = PurchaseOrderItem::findOrFail($itemData['id']);
                    if ($item->purchase_order_id != $purchaseOrder->id) abort(403);
                    $item->update([
                        'quantity_requested' => $itemData['quantity_requested'],
                        'unit_cost'          => $itemData['unit_cost'] ?? $item->unit_cost,
                    ]);
                    $existingIds[] = $item->id;
                } else {
                    $product = Product::findOrFail($itemData['product_id']);
                    $newItem = PurchaseOrderItem::create([
                        'purchase_order_id'  => $purchaseOrder->id,
                        'product_id'         => $product->id,
                        'product_name'       => $product->name,
                        'quantity_requested' => $itemData['quantity_requested'],
                        'unit_cost'          => $itemData['unit_cost'] ?? $product->cost,
                        'status'             => 'pending',
                    ]);
                    $existingIds[] = $newItem->id;
                }
            }

            PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                ->whereNotIn('id', $existingIds)
                ->delete();

            ActivityLog::record('po_updated', ['reference' => $purchaseOrder->reference], $purchaseOrder);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
                         ->with('success', "PO {$purchaseOrder->reference} updated.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizePO(PurchaseOrder $po): void
    {
        if ($po->shop_id !== auth()->user()->shop_id) abort(403);
    }

    /**
     * Log stock movement for purchase order receipts.
     */
    private function logStockMovement($product, $branch, $type, $quantity, $beforeQty, $afterQty, $reference = null, $notes = null)
    {
        // If $product is not already a model, fetch it (but we always pass the model)
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