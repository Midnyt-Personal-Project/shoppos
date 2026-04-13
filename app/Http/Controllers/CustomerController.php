<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Customer::where('shop_id', $user->shop_id);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('has_debt') && $request->has_debt === 'yes') {
            $query->where('outstanding_balance', '>', 0);
        }

        $customers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create(array_merge($data, ['shop_id' => $user->shop_id]));

        if ($request->expectsJson()) {
            return response()->json($customer);
        }
        return redirect()->route('customers.index')->with('success', 'Customer added.');
    }

    public function show(Customer $customer)
    {
        $this->authorize($customer);
        $customer->load(['sales.items', 'payments']);
        $recentSales = $customer->sales()->with('items')->orderByDesc('created_at')->limit(10)->get();
        return view('customers.show', compact('customer', 'recentSales'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorize($customer);
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email',
            'address'      => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);
        $customer->update($data);
        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    /** Record a debt repayment */
    public function repayDebt(Request $request, Customer $customer)
    {
        $this->authorize($customer);
        $request->validate(['amount' => 'required|numeric|min:0.01']);

        $amount = min($request->amount, $customer->outstanding_balance);
        $customer->reduceDebt($amount);

        // Find the latest unpaid sale and record a payment
        $sale = Sale::where('customer_id', $customer->id)
                    ->where('payment_status', '!=', 'paid')
                    ->orderByDesc('created_at')
                    ->first();

        if ($sale) {
            Payment::create([
                'sale_id'     => $sale->id,
                'customer_id' => $customer->id,
                'method'      => $request->method ?? 'cash',
                'amount'      => $amount,
                'notes'       => 'Debt repayment',
            ]);
            $newBalance = $sale->balance_due - $amount;
            $sale->update([
                'balance_due'    => max(0, $newBalance),
                'amount_paid'    => $sale->amount_paid + $amount,
                'payment_status' => $newBalance <= 0 ? 'paid' : 'partial',
            ]);
        }

        return response()->json(['success' => true, 'new_balance' => $customer->fresh()->outstanding_balance]);
    }

    private function authorize(Customer $customer): void
    {
        if ($customer->shop_id !== auth()->user()->shop_id) abort(403);
    }
}