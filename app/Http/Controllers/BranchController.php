<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $shopId   = auth()->user()->shop_id;
        $branches = Branch::where('shop_id', $shopId)->withCount('users')->get();
        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone'   => 'nullable|string|max:20',
        ]);

        Branch::create(array_merge($data, ['shop_id' => $user->shop_id]));
        return redirect()->route('branches.index')->with('success', 'Branch created.');
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorizeBranch($branch);
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);
        $branch->update($data);
        return redirect()->route('branches.index')->with('success', 'Branch updated.');
    }

    private function authorizeBranch(Branch $branch): void
    {
        if ($branch->shop_id !== auth()->user()->shop_id) abort(403);
    }
}