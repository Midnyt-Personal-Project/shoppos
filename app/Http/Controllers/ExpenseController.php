<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\{Branch, Expense, ExpenseCategory};

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $branchId = current_branch()->id;
        $query = Expense::with('user')->where('branch_id', $branchId);

        if ($request->filled('date_from')) $query->whereDate('expense_date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('expense_date', '<=', $request->date_to);
        if ($request->filled('category')) {
            if ($request->category === 'uncategorized') {
                $query->whereNull('category');
            } else {
                $query->where('category', $request->category);
            }
        }

        $expenses    = $query->orderByDesc('expense_date')->paginate(20)->withQueryString();
        $totalAmount = $query->sum('amount');

        // Fetch categories for filter dropdown
        $categories = ExpenseCategory::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'name');

        return view('expenses.index', compact('expenses', 'totalAmount', 'categories'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'notes'        => 'nullable|string',
             'receipt'      => 'nullable|file|mimes:jpg,jpeg,png,pdf', // Optional receipt upload
        ]);

        // Category handling: only admin/manager can set a category
        if ($user->isAdmin() || $user->isManager()) {
            $data['category'] = $request->validate(['category' => 'nullable|string|max:100'])['category'];
        } else {
            // Cashier: leave category NULL
            $data['category'] = null;
        }

         // Handle receipt upload
        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('receipts', 'public');
            $data['receipt_path'] = $path;
        }

        Expense::create(array_merge($data, [
            'branch_id' => current_branch()->id,
            'user_id'   => $user->id,
        ]));

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense)
    {
        $this->authorizeExpense($expense);
        $user = auth()->user();
        if (! $user->isAdmin() && ! $user->isManager()) {
            abort(403, 'Only admins and managers can edit expenses.');
        }

        $categories = ExpenseCategory::where('branch_id', current_branch()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'name');

        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorizeExpense($expense);
        $user = auth()->user();
        if (! $user->isAdmin() && ! $user->isManager()) {
            abort(403, 'Only admins and managers can edit expenses.');
        }

        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'nullable|string|max:100',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'notes'        => 'nullable|string',
            'receipt'      => 'nullable|file|mimes:jpg,jpeg,png,pdf', // Optional receipt upload
        ]);

        // Handle receipt upload
          // Handle receipt replacement
        if ($request->hasFile('receipt')) {
            // Delete old receipt if exists
            if ($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path)) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $path = $request->file('receipt')->store('receipts', 'public');
            $data['receipt_path'] = $path;
        }

        $expense->update($data);

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

     public function downloadReceipt(Expense $expense)
    {
        $this->authorizeExpense($expense); // ensures branch permission
        if (!$expense->receipt_path) {
            abort(404, 'Receipt not found.');
        }
        return Storage::disk('public')->download($expense->receipt_path);
    }
    public function destroy(Expense $expense)
    {
        $this->authorizeExpense($expense);
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    public function report(Request $request)
{
    $user = auth()->user();
    $branchId = current_branch()->id;
    $isAdminOrManager = $user->isAdmin() || $user->isManager();

    // Build query – if admin/manager, allow all branches; otherwise only own branch
    $query = Expense::with(['user', 'branch']);

    if (!$isAdminOrManager) {
        $query->where('branch_id', $branchId);
    }

    // Branch filter (only for admin/manager)
    if ($request->filled('branch_id') && $isAdminOrManager) {
        $query->where('branch_id', $request->branch_id);
    }

    // Date filter
    if ($request->filled('date_from')) {
        $query->whereDate('expense_date', '>=', $request->date_from);
    }
    if ($request->filled('date_to')) {
        $query->whereDate('expense_date', '<=', $request->date_to);
    }

    // Category filter
    if ($request->filled('category')) {
        if ($request->category === 'uncategorized') {
            $query->whereNull('category');
        } else {
            $query->where('category', $request->category);
        }
    }

    // Summary data
    $totalAmount = $query->sum('amount');
    $expenses = $query->orderBy('expense_date', 'desc')->get();

    // Group by category
    $categoryTotals = $expenses->groupBy('category')->map(fn($items) => $items->sum('amount'));

    // Group by month for line chart
    $monthlyTotals = $expenses->groupBy(fn($e) => $e->expense_date->format('Y-m'))
        ->map(fn($items) => $items->sum('amount'))
        ->sortKeys();

    // Group by day for bar chart (if short period)
    $dailyTotals = $expenses->groupBy(fn($e) => $e->expense_date->format('Y-m-d'))
        ->map(fn($items) => $items->sum('amount'))
        ->sortKeys();

    // Branches for filter
    $branches = Branch::where('shop_id', $user->shop_id)->where('is_active', true)->get();

    // Categories for filter
    $categories = ExpenseCategory::where('branch_id', $branchId)
        ->where('is_active', true)
        ->orderBy('name')
        ->pluck('name', 'name');

    return view('reports.expense', compact(
        'expenses', 'totalAmount', 'categoryTotals', 'monthlyTotals', 'dailyTotals',
        'branches', 'categories', 'isAdminOrManager'
    ));
}

    private function authorizeExpense(Expense $expense): void
    {
        if ($expense->branch_id !== current_branch()->id) abort(403);
    }

}