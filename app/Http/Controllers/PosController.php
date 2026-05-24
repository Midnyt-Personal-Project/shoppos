<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};

use App\Events\{SaleCompleted, StockLow};
use App\Models\{ActivityLog, BranchStock, Customer, Payment, Product, Sale, SaleItem, TaxRate};

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


    // <-- add this at the top of your controller

    // ... rest of your controller code ...

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

        // Get all active tax rates once
        $taxRates = TaxRate::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['name', 'rate']);

        $discount   = (float) ($request->discount ?? 0);
        $customerId = $request->customer_id ?: null;

        DB::beginTransaction();
        try {
            // ── 1. Subtotal after item discounts (no tax) ─────────────────────
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

            // ── 2. Apply global discount to get taxable amount ─────────────────
            $taxableTotal = max(0, $subtotal - $discount);

            // ── 3. Calculate tax on the discounted total (all active tax rates) ─
            $taxBreakdown = [];
            $taxTotal = 0;
            foreach ($taxRates as $tax) {
                $taxAmount = round($taxableTotal * ($tax->rate / 100), 2);
                $taxBreakdown[$tax->name] = $taxAmount;
                $taxTotal += $taxAmount;
            }

            // ── 4. Final total ────────────────────────────────────────────────
            $total = $taxableTotal + $taxTotal;

            // ── 5. Allocate tax proportionally to each sale item ───────────────
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

            // ── Payment & sale creation ────────────────────────────────────────
            $totalPaid = round(collect($request->payments)->sum('amount'), 2);
            $change = round(max(0, $totalPaid - $total), 2);
            $balanceDue = round(max(0, $total - $totalPaid), 2);
            $paymentStatus = $balanceDue <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

            $reference = Sale::generateReference($branchId);

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

            // ── Create sale items and deduct stock ────────────────────────────
            foreach ($saleItems as $si) {
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

                // Only deduct stock if it's a product (not a service)
                if ($si['product']->type === 'product') {
                    BranchStock::where('branch_id', $branchId)
                        ->where('product_id', $si['product']->id)
                        ->decrement('quantity', $si['qty']);
                }
            }

            // ── Check low stock and dispatch event ────────────────────────────
            $branch = $user->branch ?? \App\Models\Branch::find($branchId);
            $lowStockItems = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['id']);
                if (!$product || $product->type !== 'product') continue;  // Skip services

                $branchStock = BranchStock::where('branch_id', $branchId)
                    ->where('product_id', $product->id)
                    ->first();

                if ($branchStock) {
                    $alertLevel = $branchStock->low_stock_alert ?? 5;
                    if ($branchStock->quantity <= $alertLevel) {
                        $lowStockItems[] = [
                            'name'  => $product->name,
                            'qty'   => $branchStock->quantity,
                            'alert' => $alertLevel,
                        ];
                    }
                }
            }

            if (!empty($lowStockItems)) {
                event(new StockLow($branch, $lowStockItems));
            }

            // ── Create payments ───────────────────────────────────────────────
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

            // ── Handle customer debt ──────────────────────────────────────────
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

            // Dispatch SaleCompleted event after commit
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
    //    public function checkout(Request $request)
    // {
    //     $request->validate([
    //         'items'             => 'required|array|min:1',
    //         'items.*.id'        => 'required|exists:products,id',
    //         'items.*.qty'       => 'required|numeric|min:0.01',
    //         'items.*.price'     => 'required|numeric|min:0',
    //         'payments'          => 'required|array|min:1',
    //         'payments.*.method' => 'required|in:cash,mobile_money,card',
    //         'payments.*.amount' => 'required|numeric|min:0',
    //         'discount'          => 'nullable|numeric|min:0',
    //         // 'tax'               => 'nullable|numeric|min:0',
    //         'customer_id'       => 'nullable|exists:customers,id',
    //     ]);

    //     $user       = auth()->user();
    //     $branchId   = $user->branch_id;
    //      $shop       = $user->shop; 
    //     $discount   = (float) ($request->discount ?? 0);
    //     $tax        = (float) ($shop->defaultTaxRate ?? 0);
    //     $customerId = $request->customer_id ?: null;

    //     DB::beginTransaction();
    //     try {
    //         $subtotal = 0;
    //         $saleItems = [];
    //         $productIds = [];

    //         foreach ($request->items as $item) {
    //             $product = Product::findOrFail($item['id']);
    //             $qty = (float) $item['qty'];
    //             $price = (float) $item['price'];
    //             $itemDisc = (float) ($item['discount'] ?? 0);
    //             $lineTotal = ($price * $qty) - $itemDisc;
    //             $subtotal += $lineTotal;

    //             $saleItems[] = compact('product', 'qty', 'price', 'itemDisc', 'lineTotal');
    //             $productIds[] = $product->id;
    //         }

    //         $total = $subtotal - $discount + $tax;
    //         $totalPaid = collect($request->payments)->sum('amount');
    //         $change = max(0, $totalPaid - $total);
    //         $balanceDue = max(0, $total - $totalPaid);
    //         $paymentStatus = $balanceDue <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

    //         // Generate reference using your Sale model method
    //         $reference = Sale::generateReference($branchId);

    //         // Create sale
    //         $sale = Sale::create([
    //             'reference'      => $reference,
    //             'branch_id'      => $branchId,
    //             'user_id'        => $user->id,
    //             'customer_id'    => $customerId,
    //             'subtotal'       => $subtotal,
    //             'discount'       => $discount,
    //             'tax'            => $tax,
    //             'total'          => $total,
    //             'amount_paid'    => $totalPaid,
    //             'change'         => $change,
    //             'balance_due'    => $balanceDue,
    //             'status'         => 'completed',
    //             'payment_status' => $paymentStatus,
    //             'notes'          => $request->notes ?? null,
    //         ]);

    //         // Create sale items and deduct stock
    //         foreach ($saleItems as $si) {
    //             SaleItem::create([
    //                 'sale_id'      => $sale->id,
    //                 'product_id'   => $si['product']->id,
    //                 'product_name' => $si['product']->name,
    //                 'quantity'     => $si['qty'],
    //                 'price'        => $si['price'],
    //                 'discount'     => $si['itemDisc'],
    //                 'total'        => $si['lineTotal'],
    //                 'cost'         => $si['product']->cost,
    //             ]);

    //             // Deduct stock
    //             BranchStock::where('branch_id', $branchId)
    //                 ->where('product_id', $si['product']->id)
    //                 ->decrement('quantity', $si['qty']);
    //         }

    //         // Create payments
    //         foreach ($request->payments as $pay) {
    //             Payment::create([
    //                 'sale_id'     => $sale->id,
    //                 'customer_id' => $customerId,
    //                 'method'      => $pay['method'],
    //                 'amount'      => $pay['amount'],
    //                 'status'      => 'completed',
    //             ]);
    //         }

    //         // Handle customer debt (outstanding_balance)
    //         $newBalance = null;
    //         if ($customerId) {
    //             $customer = Customer::find($customerId);
    //             if ($balanceDue > 0) {
    //                 $customer->increment('outstanding_balance', $balanceDue);
    //                 $newBalance = $customer->outstanding_balance;
    //             } elseif ($totalPaid > $total && $customer->outstanding_balance > 0) {
    //                 $excess = $totalPaid - $total;
    //                 $reduction = min($excess, $customer->outstanding_balance);
    //                 $customer->decrement('outstanding_balance', $reduction);
    //                 $newBalance = $customer->outstanding_balance;
    //             } else {
    //                 $newBalance = $customer->outstanding_balance;
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success'     => true,
    //             'sale_id'     => $sale->id,
    //             'reference'   => $sale->reference,
    //             'change'      => $change,
    //             'new_balance' => $newBalance,
    //         ]);
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Checkout error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Server error: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
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
