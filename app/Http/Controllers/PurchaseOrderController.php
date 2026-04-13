<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ActivityLog, BranchStock, Product, PurchaseOrder, PurchaseOrderItem};

class PurchaseOrderController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = PurchaseOrder::with(['creator', 'branch', 'items'])
            ->where('shop_id', $user->shop_id);

        // Non-admins only see their own branch
        if (! $user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
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

        // Pending count badge for admins
        $pendingCount = $user->isAdmin()
            ? PurchaseOrder::where('shop_id', $user->shop_id)->where('status', 'pending')->count()
            : 0;
        // dd($branches,$orders);
        return view('purchase-orders.index', compact('orders', 'branches', 'pendingCount'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create()
    {
        $user     = auth()->user();
        $products = Product::forShop($user->shop_id)
            ->active()
            ->with(['stocks' => fn($q) => $q->where('branch_id', $user->branch_id)])
            ->orderBy('name')
            ->get();

        return view('purchase-orders.create', compact('products'));
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
                'reference'      => PurchaseOrder::generateReference($user->branch_id),
                'shop_id'        => $user->shop_id,
                'branch_id'      => $user->branch_id,
                'created_by'     => $user->id,
                'supplier_name'  => $request->supplier_name,
                'supplier_phone' => $request->supplier_phone,
                'notes'          => $request->notes,
                'expected_at'    => $request->expected_at,
                'status'         => 'pending', // auto-submit when saved
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

    // ── Show / Detail ─────────────────────────────────────────────────────────

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorizePO($purchaseOrder);
        $purchaseOrder->load(['items.product', 'creator', 'approver', 'branch.shop']);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    // ── Approve / Reject (admin only) ─────────────────────────────────────────

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

    // ── Receive items (updates stock) ─────────────────────────────────────────

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
        $item->update([
            'quantity_received' => $request->quantity_received,
            'status'            => $request->item_status,
            'notes'             => $request->notes,
        ]);

        BranchStock::firstOrCreate(
            ['branch_id' => $po->branch_id, 'product_id' => $item->product_id],
            ['quantity' => 0, 'low_stock_alert' => 5]
        );

BranchStock::where('branch_id', $po->branch_id)
           ->where('product_id', $item->product_id)
           ->increment('quantity', $request->quantity_received);

        ActivityLog::record('po_item_received', ['reference' => $po->reference, 'product_id' => $item->product_id], $item);

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }

    return response()->json(['success' => true, 'message' => 'Item received and stock updated.']);
}

    // ── Receive ALL at once (quick receive) ───────────────────────────────────

    public function receiveAll(PurchaseOrder $purchaseOrder)
    {
        $this->authorizePO($purchaseOrder);

        if (! $purchaseOrder->isApproved()) {
            return back()->with('error', 'Order must be approved first.');
        }

        DB::beginTransaction();
        try {
            foreach ($purchaseOrder->items as $item) {
                if ($item->status === 'received') continue;

                $item->update([
                    'quantity_received' => $item->quantity_requested,
                    'status'            => 'received',
                ]);

                BranchStock::firstOrCreate(
                    ['branch_id' => $purchaseOrder->branch_id, 'product_id' => $item->product_id],
                    ['quantity' => 0, 'low_stock_alert' => 5]
                );
                // Replace restore() with increment()
                BranchStock::where('branch_id', $purchaseOrder->branch_id)
                        ->where('product_id', $item->product_id)
                        ->increment('quantity', $item->quantity_requested);
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

    // ── Delete (draft/rejected only) ──────────────────────────────────────────

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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizePO(PurchaseOrder $po): void
    {
        if ($po->shop_id !== auth()->user()->shop_id) abort(403);
    }
}