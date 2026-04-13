<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Sale::with(['user', 'customer', 'items'])
            ->where('branch_id', $user->branch_id);

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

    private function authorizeSale(Sale $sale): void
    {
        if ($sale->branch_id !== auth()->user()->branch_id) abort(403);
    }
}