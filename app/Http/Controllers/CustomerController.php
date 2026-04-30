<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};
use App\Models\{Customer, Payment, Sale};

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
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $customer = Customer::create([
            'shop_id'             => auth()->user()->shop_id,
            'name'                => $request->name,
            'phone'               => $request->phone,
            'email'               => $request->email,
            'outstanding_balance' => 0,
        ]);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $this->authorizeOwner($customer);
        
        $customer->load(['sales.items', 'payments']);
        $recentSales = $customer->sales()->orderByDesc('created_at')->limit(10)->get();
        $unpaidSales = $customer->sales()
            ->where('payment_status', '!=', 'paid')
            ->orderByDesc('created_at')
            ->get(['id', 'reference', 'balance_due', 'total', 'payment_status']);

        return view('customers.show', compact('customer', 'recentSales', 'unpaidSales'));
    }

    public function repayDebt(Request $request, Customer $customer)
{
    $this->authorizeOwner($customer);

    $request->validate([
        'payments' => 'required|array|min:1',
        'payments.*.sale_id' => 'required|exists:sales,id',
        'payments.*.amount' => 'required|numeric|min:0.01',
        'method' => 'required|string',
        'notes' => 'nullable|string',
    ]);

    try {
        return DB::transaction(function () use ($request, $customer) {
            $user = auth()->user();
            $paymentsInput = $request->input('payments');
            $method = $request->input('method');
            
            $totalPaid = 0;
            $receiptData = [];

            foreach ($paymentsInput as $item) {
                $sale = Sale::where('customer_id', $customer->id)
                    ->where('id', $item['sale_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $amountToPay = round($item['amount'], 2);
                if ($amountToPay > $sale->balance_due) $amountToPay = $sale->balance_due;
                if ($amountToPay <= 0) continue;

                Payment::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $customer->id,
                    'amount' => $amountToPay,
                    'method' => $method,
                    'notes' => $request->notes,
                ]);

                $newBalance = max(0, $sale->balance_due - $amountToPay);
                $sale->update([
                    'balance_due' => $newBalance,
                    'amount_paid' => $sale->amount_paid + $amountToPay,
                    'payment_status' => $newBalance <= 0 ? 'paid' : 'partial',
                ]);

                $totalPaid += $amountToPay;
                $receiptData[] = [
                    'reference' => $sale->reference,
                    'amount' => $amountToPay,
                ];
            }

            $customer->decrement('outstanding_balance', $totalPaid);

            return response()->json([
                'success' => true,
                'shop_name' => $user->shop->name ?? 'Our Shop',
                'branch_name' => $user->branch->name ?? 'Main Branch',
                'branch_phone' => $user->branch->phone ?? '',
                'currency' => $user->shop->currency_symbol ?? '₵',
                'cashier' => $user->name,
                'customer_name' => $customer->name,
                'date' => now()->format('d/m/Y H:i'),
                'receipt_no' => 'PYMT-' . strtoupper(dechex(time())),
                'items' => $receiptData,
                'total_paid' => $totalPaid,
                'remaining_debt' => $customer->fresh()->outstanding_balance,
                'method' => ucfirst(str_replace('_', ' ', $method))
            ]);
        });
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

    private function authorizeOwner(Customer $customer)
    {
        if ($customer->shop_id !== auth()->user()->shop_id) abort(403);
    }
}