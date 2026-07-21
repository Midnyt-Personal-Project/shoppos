<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

use App\Mail\ReceiptMail;
use App\Models\{Customer, Sale};

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Sale::with(['user', 'customer', 'items'])
            ->where('branch_id', current_branch()->id);

        if ($request->filled('search')) {
            $query->where('reference', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        $this->authorizeSale($sale);
        $sale->load(['items.product', 'payments', 'customer', 'user', 'branch.shop']);
        return view('sales.show', compact('sale'));
    }

    public function refundView(Sale $sale)
    {
        $this->authorizeSale($sale);
        $sale->load(['items.product']);
        return view('sales.refund', compact('sale'));
    }

    public function receiptData(Sale $sale)
{
    $sale->load(['items.product', 'payments', 'customer', 'user', 'branch.shop']);
    return response()->json($sale);
}

public function emailReceipt(Request $request, Sale $sale)
{
    $request->validate([
        'email' => 'nullable|email',
    ]);

    $email = $request->email ?? $sale->customer?->email;

    if (!$email) {
        return response()->json([
            'success' => false,
            'message' => 'No email address available for this customer.',
        ], 422);
    }

    $sale->load(['items.product', 'payments', 'customer', 'user', 'branch.shop']);

    // Queue the email instead of sending immediately
    Mail::to($email)->queue(new ReceiptMail($sale));

    return response()->json([
        'success' => true,
        'message' => 'Receipt email has been queued and will be sent shortly to ' . $email,
    ]);
}

public function invoicePreview(Request $request)
{
    $validated = $request->validate([
        'items' => 'required|array|min:1',
        'items.*.id' => 'required|integer',
        'items.*.name' => 'required|string',
        'items.*.qty' => 'required|numeric|min:1',
        'items.*.price' => 'required|numeric|min:0',
        'customer_id' => 'nullable|exists:customers,id',
        'discount' => 'nullable|numeric|min:0',
        'tax' => 'nullable|numeric|min:0',
        'subtotal' => 'required|numeric',
        'grand_total' => 'required|numeric',
    ]);

    $shop = auth()->user()->shop;
    $customer = $validated['customer_id']
        ? Customer::find($validated['customer_id'])
        : null;

    return view('pos.invoice-print', [
        'shop' => $shop,
        'customer' => $customer,
        'items' => $validated['items'],
        'subtotal' => $validated['subtotal'],
        'discount' => $validated['discount'] ?? 0,
        'tax' => $validated['tax'] ?? 0,
        'grandTotal' => $validated['grand_total'],
        'reference' => 'INV-' . strtoupper(uniqid()),
        'date' => now()->format('d M Y, h:i A'),
    ]);
}

    private function authorizeSale(Sale $sale): void
{
    if ($sale->branch_id !== current_branch()->id) abort(403);
}
}