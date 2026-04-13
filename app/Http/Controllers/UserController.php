<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $shopId = auth()->user()->shop_id;
        $users  = User::with('branch')->where('shop_id', $shopId)->orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users',
            'phone'     => 'nullable|string|max:20',
            'password'  => 'required|string|min:6',
            'role'      => 'required|in:admin,manager,cashier',
            'branch_id' => 'required|exists:branches,id',
        ]);

        User::create([
            'shop_id'   => $user->shop_id,
            'branch_id' => $data['branch_id'],
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'],
            'role'      => $data['role'],
            'password'  => Hash::make($data['password']),
        ]);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeUser($user);
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'role'      => 'required|in:admin,manager,cashier',
            'branch_id' => 'required|exists:branches,id',
            'is_active' => 'boolean',
            'password'  => 'nullable|string|min:6',
        ]);

        $payload = ['name' => $data['name'], 'role' => $data['role'], 'branch_id' => $data['branch_id'], 'is_active' => $data['is_active'] ?? $user->is_active];
        if ($data['password']) $payload['password'] = Hash::make($data['password']);

        $user->update($payload);
        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $this->authorizeUser($user);
        $user->update(['is_active' => false]);
        return redirect()->route('users.index')->with('success', 'User deactivated.');
    }

    private function authorizeUser(User $user): void
    {
        if ($user->shop_id !== auth()->user()->shop_id) abort(403);
        if ($user->id === auth()->id()) abort(403, 'Cannot modify your own account.');
    }
}