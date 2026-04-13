<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\{SaleCompleted, StockLow};
use App\Models\{ActivityLog, BranchStock, Customer, Payment, Product, Sale, SaleItem};

class PosController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;
        $customers = Customer::where('shop_id', $user->shop_id)->orderBy('name')->get();

        $products = Product::where('shop_id', $user->shop_id)
            ->where('is_active', true)
            ->with(['stocks' => fn($q) => $q->where('branch_id', $branchId)])
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'barcode'  => $p->barcode ?? '',
                'price'    => (float) $p->price,
                'cost'     => (float) $p->cost,
                'unit'     => $p->unit,
                'category' => $p->category ?? '',
                'image'    => $p->image,
                'stock'    => $p->stocks->first()?->quantity ?? 0,
            ])
            ->values();

        $categories = $products->pluck('category')->filter()->unique()->sort()->values();

        return view('pos.index', compact('customers', 'products', 'categories'));
    }

    public function searchProduct(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;

        $products = Product::forShop($user->shop_id)
            ->active()
            ->search($request->q)
            ->with(['stocks' => fn($q) => $q->where('branch_id', $branchId)])
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id'      => $p->id,
                'name'    => $p->name,
                'barcode' => $p->barcode,
                'price'   => $p->price,
                'cost'    => $p->cost,
                'unit'    => $p->unit,
                'stock'   => $p->stocks->first()?->quantity ?? 0,
                'image'   => $p->image,
            ]);

        return response()->json($products);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.id'        => 'required|exists:products,id',
            'items.*.qty'       => 'required|numeric|min:0.01',
            'items.*.price'     => 'required|numeric|min:0',
            'payments'          => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,mobile_money,card,credit',
            'payments.*.amount' => 'required|numeric|min:0',
        ]);

        $user       = auth()->user();
        $branchId   = $user->branch_id;
        $discount   = (float) ($request->discount ?? 0);
        $tax        = (float) ($request->tax ?? 0);
        $customerId = $request->customer_id ?: null;

        DB::beginTransaction();
        try {
            $subtotal       = 0;
            $saleItems      = [];
            $soldProductIds = [];

            foreach ($request->items as $item) {
                $product   = Product::findOrFail($item['id']);
                $qty       = (float) $item['qty'];
                $price     = (float) $item['price'];
                $itemDisc  = (float) ($item['discount'] ?? 0);
                $lineTotal = ($price * $qty) - $itemDisc;
                $subtotal += $lineTotal;

                $saleItems[]      = compact('product', 'qty', 'price', 'itemDisc', 'lineTotal');
                $soldProductIds[] = $product->id;
            }

            $total         = $subtotal - $discount + $tax;
            $totalPaid     = collect($request->payments)->sum('amount');
            $balanceDue    = max(0, $total - $totalPaid);
            $change        = max(0, $totalPaid - $total);
            $paymentStatus = $balanceDue <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

            $sale = Sale::create([
                'reference'      => Sale::generateReference($branchId),
                'branch_id'      => $branchId,
                'user_id'        => $user->id,
                'customer_id'    => $customerId,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'total'          => $total,
                'amount_paid'    => $totalPaid,
                'change'         => $change,
                'balance_due'    => $balanceDue,
                'status'         => 'completed',
                'payment_status' => $paymentStatus,
                'notes'          => $request->notes,
            ]);

            foreach ($saleItems as $si) {
                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $si['product']->id,
                    'product_name' => $si['product']->name,
                    'price'        => $si['price'],
                    'cost'         => $si['product']->cost,
                    'quantity'     => $si['qty'],
                    'discount'     => $si['itemDisc'],
                    'total'        => $si['lineTotal'],
                ]);

                BranchStock::deduct($branchId, $si['product']->id, $si['qty']);
            }

            foreach ($request->payments as $pay) {
                Payment::create([
                    'sale_id'     => $sale->id,
                    'customer_id' => $customerId,
                    'method'      => $pay['method'],
                    'amount'      => $pay['amount'],
                    'reference'   => $pay['reference'] ?? null,
                ]);
            }

            if ($balanceDue > 0 && $customerId) {
                Customer::find($customerId)->addDebt($balanceDue);
            }

            ActivityLog::record('sale_completed', ['reference' => $sale->reference, 'total' => $total], $sale);

            DB::commit();

            // ── Fire events AFTER commit ──────────────────────────────────────

            // Event 1: new sale notification
            SaleCompleted::dispatch($sale);

            // Event 2: low stock check for any product just sold
            $lowItems = BranchStock::with('product')
                ->where('branch_id', $branchId)
                ->whereIn('product_id', $soldProductIds)
                ->whereColumn('quantity', '<=', 'low_stock_alert')
                ->get()
                ->map(fn($s) => [
                    'name'  => $s->product->name,
                    'qty'   => $s->quantity,
                    'alert' => $s->low_stock_alert,
                ])
                ->toArray();

            if (! empty($lowItems)) {
                $branch = \App\Models\Branch::find($branchId);
                StockLow::dispatch($branch, $lowItems);
            }

            return response()->json([
                'success'   => true,
                'sale_id'   => $sale->id,
                'reference' => $sale->reference,
                'change'    => $change,
                'message'   => 'Sale completed.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items.product', 'payments', 'customer', 'user', 'branch.shop']);
        return view('pos.receipt', compact('sale'));
    }

    public function refund(Request $request, Sale $sale)
    {
        $request->validate([
            'items'              => 'required|array',
            'items.*.id'         => 'required|exists:sale_items,id',
            'items.*.return_qty' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $refundTotal = 0;
            foreach ($request->items as $ri) {
                $item = SaleItem::findOrFail($ri['id']);
                if ($item->sale_id !== $sale->id) abort(403);

                $returnQty = min($ri['return_qty'], $item->quantity - $item->returned_quantity);
                if ($returnQty <= 0) continue;

                $item->increment('returned_quantity', $returnQty);
                if ($item->returned_quantity >= $item->quantity) {
                    $item->update(['is_returned' => true]);
                }

                BranchStock::restore($sale->branch_id, $item->product_id, $returnQty);
                $refundTotal += $item->price * $returnQty;
            }

            ActivityLog::record('refund', ['sale_reference' => $sale->reference, 'refund_total' => $refundTotal], $sale);
            DB::commit();

            return response()->json(['success' => true, 'refund_total' => $refundTotal]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}