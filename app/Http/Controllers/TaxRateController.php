<?php

namespace App\Http\Controllers;

use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxRateController extends Controller
{
     

    public function index()
    {
        return response()->json(TaxRate::orderBy('name')->get());
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
            'created_by' => Auth::id(),
        ]);

        return response()->json($tax, 201);
    }

    public function update(Request $request, TaxRate $tax)
    {
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
        $tax->delete();
        return response()->json(null, 204);
    }
}
