<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use Carbon\Carbon;

use App\Models\{ActivityLog, Branch, Customer, Expense, Product, Sale};

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user   = auth()->user();
        $shopId = $user->shop_id;

        // For cashiers, scope to their branch only
        $branchId = current_branch()->id;

        $branch = Branch::find($branchId);

        // Today's stats
        $todaySalesQuery = Sale::where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->where('status', 'completed');

        

        $todayRevenue  = (clone $todaySalesQuery)->sum('total');
        $todayCount    = (clone $todaySalesQuery)->count();
        $todayExpenses = Expense::where('branch_id', $branchId)
            ->whereDate('expense_date', today())->sum('amount');

        // Profit = revenue - cost of goods sold
        $todaySales = (clone $todaySalesQuery)->with('items')->get();
        $todayCOGS  = $todaySales->flatMap->items->sum(fn($i) => $i->cost * $i->quantity);
        $todayProfit = $todayRevenue - $todayCOGS - $todayExpenses;

        // Monthly revenue chart (last 30 days)
        $revenueChart = Sale::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->subDays(29), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        // Fill missing days with 0
        $chartDates  = collect();
        $chartValues = collect();
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $chartDates->push(now()->subDays($i)->format('d M'));
            $chartValues->push($revenueChart[$d] ?? 0);
        }

        // Top products
        $topProducts = \App\Models\SaleItem::select(
            'product_id',
            'product_name',
            DB::raw('MAX(price) as price'),
            DB::raw('SUM(quantity) as qty_sold'),
            DB::raw('SUM(total) as revenue')
        )
        ->groupBy('product_id', 'product_name')
        ->orderByDesc('qty_sold')
        ->limit(5)
        ->get();

        // Low stock alerts
        $lowStock = \App\Models\BranchStock::with('product')
            ->where('branch_id', current_branch()->id)
            ->whereColumn('quantity', '<=', 'low_stock_alert')
            ->get();

        // Outstanding debts
        $totalDebt = Customer::where('shop_id', $shopId)->sum('outstanding_balance');

        // All branches summary (admins only)
        $branchesSummary = $user->isAdmin()
            ? Branch::where('shop_id', $shopId)->with('sales')->get()
            : collect();

   

// Count products (via BranchStock)
$productCount = Product::where('type', 'product')
    ->whereHas('stocks', fn($q) => $q->where('branch_id', $branchId))
    ->count();

// Count services (via ServiceBranches)
$serviceCount = Product::where('type', 'service')
    ->whereHas('serviceBranches', fn($q) => $q->where('branch_id', $branchId))
    ->count();

// Total
$products = $productCount + $serviceCount;
        // recent sales
       
        $RecentSales = Sale::where('branch_id', current_branch()->id)->with(['items','user'])->latest()->take(5)->get();
        // dd($RecentSales);
   

        //Active users
        $activeUsersByRole = ActivityLog::whereDate('activity_logs.created_at', Carbon::today())
            ->join('users', 'activity_logs.user_id', '=', 'users.id')
            ->select('users.role', DB::raw('COUNT(DISTINCT users.id) as count'))
            ->groupBy('users.role')
            ->pluck('count', 'role');


        return view('dashboard.index', compact(
            'branch', 'todayRevenue', 'todayCount', 'todayExpenses', 'todayProfit',
            'products','activeUsersByRole','RecentSales',
            'chartDates', 'chartValues', 'topProducts', 'lowStock', 'totalDebt', 'branchesSummary'
        ));
    }
}