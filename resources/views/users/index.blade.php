@extends('layouts.app')
@section('title','Staff')
@section('page-title','Staff Management')

@section('content')
<div class="space-y-5" x-data="{ showAdd: false, editing: null }">

    <div class="flex items-center justify-between">
        <p class="text-slate-400 text-sm">{{ $users->count() }} staff members</p>
        <button @click="showAdd = true" class="btn-primary">+ Add Staff</button>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800 text-slate-500 text-xs uppercase tracking-wider">
                    <th class="text-left px-5 py-3">Name</th>
                    <th class="text-left px-3 py-3">Email</th>
                    <th class="text-left px-3 py-3">Branch</th>
                    <th class="text-center px-3 py-3">Role</th>
                    <th class="text-center px-3 py-3">Status</th>
                    <th class="text-right px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach($users as $user)
                <tr class="hover:bg-white/[0.02]">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full bg-green-600/20 flex items-center justify-center">
                                <span class="text-green-400 text-xs font-bold">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <span class="text-white">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-3 py-3 text-slate-400 text-xs">{{ $user->email }}</td>
                    <td class="px-3 py-3 text-slate-400">{{ $user->branch?->name ?? '—' }}</td>
                    <td class="px-3 py-3 text-center">
                        @php $rc = ['owner'=>'bg-purple-500/10 text-purple-400','admin'=>'bg-blue-500/10 text-blue-400','manager'=>'bg-cyan-500/10 text-cyan-400','cashier'=>'bg-slate-700 text-slate-300']; @endphp
                        <span class="badge {{ $rc[$user->role] ?? '' }}">{{ ucfirst($user->role) }}</span>
                    </td>
                    <td class="px-3 py-3 text-center">
                        <span class="badge {{ $user->is_active ? 'bg-green-500/10 text-green-400' : 'bg-slate-700 text-slate-500' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                              class="inline" onsubmit="return confirm('Deactivate this user?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-slate-600 hover:text-red-400 transition-colors text-xs">Deactivate</button>
                        </form>
                        @else
                        <span class="text-slate-700 text-xs">You</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Add Staff Modal --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-96 p-6" @click.outside="showAdd = false">
            <h3 class="text-white font-semibold mb-4">Add Staff Member</h3>
            <form method="POST" action="{{ route('users.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-slate-400 text-xs mb-1 block">Full Name *</label><input type="text" name="name" required class="input"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Email *</label><input type="email" name="email" required class="input"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Phone</label><input type="tel" name="phone" class="input"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Password *</label><input type="password" name="password" required minlength="6" class="input"></div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Role *</label>
                    <select name="role" required class="input">
                        <option value="cashier">Cashier</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Branch *</label>
                    <select name="branch_id" required class="input">
                        @foreach(\App\Models\Branch::where('shop_id', auth()->user()->shop_id)->get() as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showAdd = false" class="btn-secondary flex-1">Cancel</button>
                    <button type="submit" class="btn-primary flex-1">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection