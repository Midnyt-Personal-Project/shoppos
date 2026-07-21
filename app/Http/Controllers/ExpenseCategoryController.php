<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ExpenseCategory;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $branchId = current_branch()->id;
        $categories = ExpenseCategory::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! $user->isManager()) {
            abort(403, 'Only admins and managers can create categories.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,NULL,id,branch_id,' . current_branch()->id,
        ]);

        $category = ExpenseCategory::create([
            'branch_id'  => current_branch()->id,
            'created_by' => $user->id,
            'name'       => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created.',
            'id'      => $category->id,
            'name'    => $category->name,
        ]);
    }

    public function destroy(ExpenseCategory $category)
    {
        if ($category->branch_id !== current_branch()->id) abort(403);
        if (! auth()->user()->isAdmin() && ! auth()->user()->isManager()) {
            abort(403);
        }
        $category->delete();
        return response()->json(['success' => true]);
    }
}