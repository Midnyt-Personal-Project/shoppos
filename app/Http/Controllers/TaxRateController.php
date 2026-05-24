<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\TaxRate;

class TaxRateController extends Controller
{
     

    public function index()
    {
        $branchId = current_branch()->id;
         return response()->json(
        TaxRate::where('branch_id', $branchId)->orderBy('name')->get()
    );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $tax = TaxRate::create([
            ...$validated,
            'branch_id' => current_branch()->id,
             'is_active'   => $validated['is_active'] ?? true,
            'created_by' => Auth::id(),
        ]);

        return response()->json($tax, 201);
    }

    public function update(Request $request, TaxRate $tax)
    {
      if ($tax->branch_id !== current_branch()->id) abort(403);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $tax->update($validated);

        return response()->json($tax);
    }

    public function destroy(TaxRate $tax)
    {
        if ($tax->branch_id !== current_branch()->id) abort(403);
        $tax->delete();
        return response()->json(null, 204);
    }
}
