<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};

use App\Events\{SaleCompleted, StockLow};
use App\Models\{ActivityLog, Branch, BranchStock, Customer, Payment, Product, Sale, SaleItem, StockMovement, TaxRate};

class PosController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $branchId = current_branch()->id;

        $customers = Customer::where('shop_id', $user->shop_id)
            ->orderBy('name')
            ->get();

        $taxRates = TaxRate::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['name', 'rate']);

        // PRODUCTS
        $products = Product::where('shop_id', $user->shop_id)
            ->where('is_active', true)
            ->where('type', 'product')
            ->whereHas('stocks', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->with([
                'stocks' => function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $stock = $p->stocks->first();

                return [
                    'id'                     => $p->id,
                    'name'                   => $p->name,
                    'barcode'                => $p->barcode ?? '',
                    'price'                  => (float) $p->price,
                    'cost'                   => (float) $p->cost,
                    'unit'                   => $p->unit,
                    'category'               => $p->category ?? '',
                    'image'                  => $p->image,
                    'stock'                  => $stock?->quantity ?? 0,
                    'type'                   => $p->type,
                    'allow_price_override'   => (bool) $p->allow_price_override,
                    'low_stock_threshold'    => $stock?->low_stock_alert,
                ];
            });

        // SERVICES
        $services = Product::where('shop_id', $user->shop_id)
            ->where('is_active', true)
            ->where('type', 'service')
            ->whereHas('serviceBranches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                return [
                    'id'                     => $s->id,
                    'name'                   => $s->name,
                    'barcode'                => $s->barcode ?? '',
                    'price'                  => (float) $s->price,
                    'cost'                   => (float) $s->cost,
                    'unit'                   => $s->unit,
                    'category'               => $s->category ?? '',
                    'image'                  => $s->image,
                    'stock'                  => 0,
                    'type'                   => $s->type,
                    'allow_price_override'   => (bool) $s->allow_price_override,
                    'low_stock_threshold'    => null,
                ];
            });

        // COMBINE PRODUCTS + SERVICES
        $allItems = $products
            ->concat($services)
            ->sortBy('name')
            ->values();

        // CATEGORIES
        $categories = $allItems
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('pos.index', [
            'customers' => $customers,
            'products'  => $allItems,
            'categories' => $categories,
            'taxRates'  => $taxRates,
        ]);
    }

    public function searchProduct(Request $request)
    {
        $user     = auth()->user();
        $branchId = current_branch()->id;

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
            'payments.*.method' => 'required|in:cash,mobile_money,card',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.reference' => 'nullable|string|max:255',
            'discount'          => 'nullable|numeric|min:0',
            'customer_id'       => 'nullable|exists:customers,id',
        ]);

        $user       = auth()->user();
        $branchId   = current_branch()->id;
        $shop       = $user->shop;

        $taxRates = TaxRate::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['name', 'rate']);

        $discount   = (float) ($request->discount ?? 0);
        $customerId = $request->customer_id ?: null;

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $saleItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['id']);
                $qty = (float) $item['qty'];
                $price = (float) $item['price'];
                $itemDisc = (float) ($item['discount'] ?? 0);
                $lineSubtotal = ($price * $qty) - $itemDisc;
                $subtotal += $lineSubtotal;

                $saleItems[] = compact('product', 'qty', 'price', 'itemDisc', 'lineSubtotal');
            }

            $taxableTotal = max(0, $subtotal - $discount);
            $taxBreakdown = [];
            $taxTotal = 0;
            foreach ($taxRates as $tax) {
                $taxAmount = round($taxableTotal * ($tax->rate / 100), 2);
                $taxBreakdown[$tax->name] = $taxAmount;
                $taxTotal += $taxAmount;
            }

            $total = $taxableTotal + $taxTotal;

            // Allocate tax proportionally
            foreach ($saleItems as &$si) {
                $lineSubtotal = $si['lineSubtotal'];
                if ($taxableTotal > 0 && $taxTotal > 0) {
                    $allocatedTax = round(($lineSubtotal / $taxableTotal) * $taxTotal, 2);
                } else {
                    $allocatedTax = 0;
                }
                $si['lineTax']   = $allocatedTax;
                $si['lineTotal'] = $lineSubtotal + $allocatedTax;
            }
            unset($si);

            $totalPaid = round(collect($request->payments)->sum('amount'), 2);
            $change = round(max(0, $totalPaid - $total), 2);
            $balanceDue = round(max(0, $total - $totalPaid), 2);
            $paymentStatus = $balanceDue <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

            // --- CREDIT LIMIT CHECK ---
            if ($customerId) {
                $customer = Customer::find($customerId);
                if ($customer && $customer->credit_limit !== null) {
                    $newBalance = $customer->outstanding_balance + $balanceDue;
                    if ($newBalance > $customer->credit_limit) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Credit limit exceeded. Outstanding debt: {$customer->outstanding_balance}, limit: {$customer->credit_limit}. This sale would add {$balanceDue}.",
                        ], 422);
                    }
                }
            }

            $reference = Sale::generateReference($branchId);

            // ─── 1. Create Sale ────────────────────────────────────────
            $sale = Sale::create([
                'reference'      => $reference,
                'branch_id'      => $branchId,
                'user_id'        => $user->id,
                'customer_id'    => $customerId,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax_total'      => $taxTotal,
                'tax_breakdown'  => $taxBreakdown,
                'total'          => $total,
                'amount_paid'    => $totalPaid,
                'change'         => $change,
                'balance_due'    => $balanceDue,
                'status'         => 'completed',
                'payment_status' => $paymentStatus,
                'notes'          => $request->notes ?? null,
            ]);

            // ─── 2. Create Sale Items & Deduct Stock ──────────────────
            $branch = Branch::find($branchId);

            foreach ($saleItems as $si) {
                // Create sale item record
                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $si['product']->id,
                    'product_name' => $si['product']->name,
                    'quantity'     => $si['qty'],
                    'price'        => $si['price'],
                    'discount'     => $si['itemDisc'],
                    'tax_rate'     => $taxRates->sum('rate'),
                    'tax_amount'   => $si['lineTax'],
                    'total'        => $si['lineTotal'],
                    'cost'         => $si['product']->cost,
                ]);

                // Only deduct stock for physical products (not services)
                if ($si['product']->type === 'product') {
                    $stock = BranchStock::where('branch_id', $branchId)
                        ->where('product_id', $si['product']->id)
                        ->first();

                    if ($stock) {
                        $before = $stock->quantity;
                        $stock->decrement('quantity', $si['qty']);
                        $after = $stock->fresh()->quantity;

                        // Log the stock movement (sale deduction)
                        $this->logStockMovement(
                            $si['product'],
                            $branch,
                            'sale',
                            -$si['qty'],
                            $before,
                            $after,
                            $sale->reference,
                            "Sale #{$sale->reference}"
                        );
                    }
                }
            }

            // ─── 3. Create Payments ────────────────────────────────────
            foreach ($request->payments as $pay) {
                Payment::create([
                    'sale_id'     => $sale->id,
                    'customer_id' => $customerId,
                    'method'      => $pay['method'],
                    'amount'      => $pay['amount'],
                    'reference'   => $pay['reference'] ?? null,
                    'status'      => 'completed',
                ]);
            }

            // ─── 4. Update Customer Balance ────────────────────────────
            $newBalance = null;
            if ($customerId) {
                $customer = Customer::find($customerId);
                if ($balanceDue > 0) {
                    $customer->increment('outstanding_balance', $balanceDue);
                    $newBalance = $customer->outstanding_balance;
                } elseif ($totalPaid > $total && $customer->outstanding_balance > 0) {
                    $excess = $totalPaid - $total;
                    $reduction = min($excess, $customer->outstanding_balance);
                    $customer->decrement('outstanding_balance', $reduction);
                    $newBalance = $customer->outstanding_balance;
                } else {
                    $newBalance = $customer->outstanding_balance;
                }
            }

            DB::commit();
            event(new SaleCompleted($sale));

            return response()->json([
                'success'     => true,
                'sale_id'     => $sale->id,
                'reference'   => $sale->reference,
                'change'      => $change,
                'new_balance' => $newBalance,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Checkout error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
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
            $branch = Branch::find($sale->branch_id);

            foreach ($request->items as $ri) {
                $item = SaleItem::findOrFail($ri['id']);
                if ($item->sale_id !== $sale->id) abort(403);

                $returnQty = min($ri['return_qty'], $item->quantity - $item->returned_quantity);
                if ($returnQty <= 0) continue;

                $item->increment('returned_quantity', $returnQty);
                if ($item->returned_quantity >= $item->quantity) {
                    $item->update(['is_returned' => true]);
                }

                // Restore stock
                $stock = BranchStock::where('branch_id', $sale->branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($stock) {
                    $before = $stock->quantity;
                    $stock->increment('quantity', $returnQty);
                    $after = $stock->fresh()->quantity;

                    // Log refund stock addition
                    $this->logStockMovement(
                        $item->product, // we need the product model, but we only have product_id – fetch it
                        $branch,
                        'refund',
                        $returnQty,
                        $before,
                        $after,
                        $sale->reference,
                        "Refund for sale #{$sale->reference}"
                    );
                }

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

    // ── Helper to log stock movements ──────────────────────────────────────

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