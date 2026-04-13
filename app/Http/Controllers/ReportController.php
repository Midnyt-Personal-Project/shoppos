<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;
        $from     = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to       = $request->date_to   ?? now()->toDateString();

        $sales = Sale::with(['user', 'customer', 'items'])
            ->where('branch_id', $branchId)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        $revenue  = $sales->sum('total');
        $cogs     = $sales->flatMap->items->sum(fn($i) => $i->cost * $i->quantity);
        $expenses = Expense::where('branch_id', $branchId)
                           ->whereBetween('expense_date', [$from, $to])
                           ->sum('amount');
        $profit   = $revenue - $cogs - $expenses;

        // Group by day for chart
        $byDay = $sales->groupBy(fn($s) => $s->created_at->format('Y-m-d'));
        $chartDates  = $byDay->keys()->map(fn($d) => date('d M', strtotime($d)));
        $chartValues = $byDay->map->sum('total')->values();

        // Top products in period
        $topProducts = SaleItem::selectRaw('product_id, product_name, SUM(quantity) as qty_sold, SUM(total) as revenue, SUM((price-cost)*quantity) as profit')
            ->whereIn('sale_id', $sales->pluck('id'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('qty_sold')
            ->limit(10)
            ->get();

        return view('reports.sales', compact(
            'sales', 'revenue', 'cogs', 'expenses', 'profit',
            'chartDates', 'chartValues', 'topProducts', 'from', 'to'
        ));
    }

    public function stock(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;

        $stocks = \App\Models\BranchStock::with('product')
            ->where('branch_id', $branchId)
            ->when($request->filled('search'), fn($q) =>
                $q->whereHas('product', fn($q2) => $q2->where('name', 'like', '%'.$request->search.'%'))
            )
            ->when($request->filter === 'low', fn($q) =>
                $q->whereColumn('quantity', '<=', 'low_stock_alert')
            )
            ->when($request->filter === 'out', fn($q) => $q->where('quantity', '<=', 0))
            ->paginate(20)->withQueryString();

        $stockValue = \App\Models\BranchStock::where('branch_id', $branchId)
            ->join('products', 'products.id', '=', 'branch_stocks.product_id')
            ->selectRaw('SUM(branch_stocks.quantity * products.cost) as total')
            ->value('total') ?? 0;

        return view('reports.stock', compact('stocks', 'stockValue'));
    }
}