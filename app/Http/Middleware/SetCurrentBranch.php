<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;

use App\Models\Branch;

class SetCurrentBranch
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            // For non-admin/owner, force their own branch_id
            if (!in_array($user->role, ['admin', 'owner']) && $user->branch_id) {
                session(['current_branch_id' => $user->branch_id]);
            }

            // If the session branch does not belong to the user's shop, forget it
            $sessionBranchId = session('current_branch_id');
            if ($sessionBranchId && !Branch::where('id', $sessionBranchId)
                    ->where('shop_id', $user->shop_id)->exists()) {
                session()->forget('current_branch_id');
            }

            // If still no branch in session, set a default
            if (!session()->has('current_branch_id')) {
                $defaultId = $user->branch_id 
                    ?? Branch::where('shop_id', $user->shop_id)->value('id');
                if ($defaultId) {
                    session(['current_branch_id' => $defaultId]);
                }
            }
        }

        return $next($request);
    }
}