<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\{BranchStock, Expense, Sale, SaleItem};

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $user     = auth()->user();
        $branchId =  current_branch()->id;
        $from     = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to       = $request->date_to   ?? now()->toDateString();

        // Base query for KPIs and chart
        $baseQuery = Sale::where('branch_id', $branchId)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->where('status', 'completed');

        // ----- KPIs -----
        $revenue = (float) $baseQuery->sum('total');

        $saleIds = $baseQuery->pluck('id');
        $cogs = 0;
        if ($saleIds->isNotEmpty()) {
            $cogs = (float) SaleItem::whereIn('sale_id', $saleIds)
                ->select(DB::raw('SUM(cost * quantity) as total_cogs'))
                ->value('total_cogs') ?? 0;
        }

        $expenses = (float) Expense::where('branch_id', $branchId)
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        $profit = $revenue - $cogs - $expenses;

        // ----- Daily revenue for chart -----
        $dailyRevenue = $baseQuery->selectRaw('DATE(created_at) as date, SUM(total) as daily_total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartDates  = $dailyRevenue->pluck('date')->map(fn($d) => date('d M', strtotime($d)));
        $chartValues = $dailyRevenue->pluck('daily_total');

        // ----- Top 10 products (full period) -----
        $topProducts = collect();
        if ($saleIds->isNotEmpty()) {
            $topProducts = SaleItem::selectRaw('product_id, product_name, SUM(quantity) as qty_sold, SUM(total) as revenue, SUM((price - cost) * quantity) as profit')
                ->whereIn('sale_id', $saleIds)
                ->groupBy('product_id', 'product_name')
                ->orderByDesc('qty_sold')
                ->limit(10)
                ->get();
        }

        // ----- Paginated sales list for the table -----
        $sales = Sale::with(['user', 'customer', 'items'])
            ->where('branch_id', $branchId)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->appends($request->query());

        return view('reports.sales', compact(
            'sales', 'revenue', 'cogs', 'expenses', 'profit',
            'chartDates', 'chartValues', 'topProducts', 'from', 'to'
        ));
    }

    public function stock(Request $request)
    {
        $user     = auth()->user();
        $branchId = current_branch()->id;
    

        $stocks = \App\Models\BranchStock::with('product')
    ->where('branch_id', $branchId)
    ->whereHas('product')   // 👈 add this line
    ->when($request->filled('search'), fn($q) =>
        $q->whereHas('product', fn($q2) =>
            $q2->whereRaw('COALESCE(name, "") LIKE ?', ['%' . $request->search . '%'])
        )
    )
    ->when($request->filter === 'low', fn($q) =>
        $q->whereColumn('quantity', '<=', 'low_stock_alert')
    )
    ->when($request->filter === 'out', fn($q) =>
        $q->where('quantity', '<=', 0)
    )
    ->paginate(20)->withQueryString();

    


        $stockValue = \App\Models\BranchStock::where('branch_id', $branchId)
            ->join('products', 'products.id', '=', 'branch_stocks.product_id')
            ->selectRaw('SUM(branch_stocks.quantity * products.cost) as total')
            ->value('total') ?? 0;
        $stockProfitValue = \App\Models\BranchStock::where('branch_id', $branchId)
            ->join('products', 'products.id', '=', 'branch_stocks.product_id')
            ->selectRaw('SUM(branch_stocks.quantity * (products.price - products.cost)) as total_profit')
            ->value('total_profit') ?? 0;

       

        return view('reports.stock', compact('stocks', 'stockValue', 'stockProfitValue'));
    }
}