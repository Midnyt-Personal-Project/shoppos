<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Branch;

class BranchSwitchController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id'
        ]);

        $user = Auth::user();
        $branch = Branch::findOrFail($request->branch_id);

        // Only admin/owner can switch, and only to branches of their own shop
        if (!in_array($user->role, ['admin', 'owner']) || $branch->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized');
        }

        session(['current_branch_id' => $branch->id]);

        return redirect()->back()->with('success', "Switched to branch: {$branch->name}");
    }
}