<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $query    = Expense::with('user')->where('branch_id', $branchId);

        if ($request->filled('date_from')) $query->whereDate('expense_date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('expense_date', '<=', $request->date_to);
        if ($request->filled('category'))  $query->where('category', $request->category);

        $expenses    = $query->orderByDesc('expense_date')->paginate(20)->withQueryString();
        $totalAmount = $query->sum('amount');

        return view('expenses.index', compact('expenses', 'totalAmount'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'notes'        => 'nullable|string',
        ]);

        Expense::create(array_merge($data, [
            'branch_id' => $user->branch_id,
            'user_id'   => $user->id,
        ]));

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->branch_id !== auth()->user()->branch_id) abort(403);
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }
}