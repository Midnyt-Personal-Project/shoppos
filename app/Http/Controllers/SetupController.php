<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash};
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\{Branch, Shop, User};

class SetupController extends Controller
{
    /** Check if setup is needed — returns JSON for the login page button */
    public function check()
    {
        return response()->json([
            'needs_setup' => User::count() === 0,
        ]);
    }

    /** Run the first-time setup */
    public function store(Request $request)
    {
        // Hard block — if ANY user exists, this endpoint is closed
        if (User::count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Setup has already been completed.',
            ], 403);
        }

        $data = $request->validate([
            // Shop
            'shop_name'       => 'required|string|max:255',
            'shop_phone'      => 'nullable|string|max:30',
            'shop_email'      => 'nullable|email|max:255',
            'shop_address'    => 'nullable|string|max:500',
            'currency'        => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:5',

            // Admin user
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // 1. Create the shop
            $shop = Shop::create([
                'name'            => $data['shop_name'],
                'slug'            => Str::slug($data['shop_name']) . '-' . Str::random(4),
                'email'           => $data['shop_email']    ?? null,
                'phone'           => $data['shop_phone']    ?? null,
                'address'         => $data['shop_address']  ?? null,
                'currency'        => $data['currency'],
                'currency_symbol' => $data['currency_symbol'],
                'is_active'       => true,
            ]);

            // 2. Create the default Main Branch
            $branch = Branch::create([
                'shop_id'   => $shop->id,
                'name'      => 'Main Branch',
                'address'   => $data['shop_address'] ?? null,
                'phone'     => $data['shop_phone']   ?? null,
                'is_active' => true,
            ]);

            // 3. Create the owner/admin user
            $user = User::create([
                'shop_id'   => $shop->id,
                'branch_id' => $branch->id,
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'role'      => 'owner',
                'password'  => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // 4. Log them in automatically
            Auth::login($user);
            $request->session()->regenerate();

            return response()->json([
                'success'  => true,
                'redirect' => route('dashboard'),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}