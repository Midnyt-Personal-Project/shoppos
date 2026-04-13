@extends('layouts.app')
@section('title','Customers')
@section('page-title','Customers')

@section('content')
<div class="space-y-5" x-data="{ showAdd: false }">

    <div class="flex items-center justify-between">
        <p class="text-slate-400 text-sm">Manage customers and track debt</p>
        <button @click="showAdd = true" class="btn-primary">+ Add Customer</button>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex gap-3 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or phone…" class="input w-60">
        <select name="has_debt" class="input w-40">
            <option value="">All Customers</option>
            <option value="yes" @selected(request('has_debt')==='yes')>With Debt Only</option>
        </select>
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800 text-slate-500 text-xs uppercase tracking-wider">
                    <th class="text-left px-5 py-3">Name</th>
                    <th class="text-left px-3 py-3">Phone</th>
                    <th class="text-right px-3 py-3">Credit Limit</th>
                    <th class="text-right px-3 py-3">Outstanding Debt</th>
                    <th class="text-right px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($customers as $customer)
                <tr class="hover:bg-white/[0.02]">
                    <td class="px-5 py-3">
                        <a href="{{ route('customers.show', $customer) }}" class="text-white hover:text-green-400 font-medium transition-colors">
                            {{ $customer->name }}
                        </a>
                    </td>
                    <td class="px-3 py-3 text-slate-400 font-mono text-xs">{{ $customer->phone ?: '—' }}</td>
                    <td class="px-3 py-3 text-right text-slate-400">₵{{ number_format($customer->credit_limit, 2) }}</td>
                    <td class="px-3 py-3 text-right">
                        @if($customer->outstanding_balance > 0)
                        <span class="text-red-400 font-bold">₵{{ number_format($customer->outstanding_balance, 2) }}</span>
                        @else
                        <span class="text-green-500 text-xs">✓ Clear</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('customers.show', $customer) }}" class="text-slate-500 hover:text-white text-xs transition-colors">View →</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-slate-600">No customers yet</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-slate-800">{{ $customers->links() }}</div>
    </div>

    {{-- Add Customer Modal --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-96 p-6" @click.outside="showAdd = false">
            <h3 class="text-white font-semibold mb-4">Add Customer</h3>
            <form method="POST" action="{{ route('customers.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-slate-400 text-xs mb-1 block">Name *</label><input type="text" name="name" required class="input"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Phone</label><input type="tel" name="phone" class="input"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Email</label><input type="email" name="email" class="input"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Credit Limit (₵)</label><input type="number" name="credit_limit" value="0" step="0.01" class="input"></div>
                <div class="flex gap-3 mt-4">
                    <button type="button" @click="showAdd = false" class="btn-secondary flex-1">Cancel</button>
                    <button type="submit" class="btn-primary flex-1">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection