<?php

use App\Models\Branch;

if (!function_exists('current_branch')) {
    function current_branch(): ?Branch
    {
        $branchId = session('current_branch_id') ?? auth()->user()?->branch_id;

        if ($branchId && $branch = Branch::find($branchId)) {
            return $branch;
        }

        // fallback: first branch of the user's shop
        $shopId = auth()->user()?->shop_id;
        return $shopId ? Branch::where('shop_id', $shopId)->first() : null;
    }
}