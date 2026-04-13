@extends('layouts.app')
@section('title','Stock Report')
@section('page-title','Stock Report')

@section('content')
<div class="space-y-5">

    <div class="grid grid-cols-3 gap-4">
        <div class="card p-5 col-span-2">
            <p class="text-slate-500 text-xs">Total Stock Value (at cost)</p>
            <p class="text-3xl font-bold text-white mt-1">₵{{ number_format($stockValue, 2) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-slate-500 text-xs">Total Products</p>
            <p class="text-3xl font-bold text-white mt-1">{{ $stocks->total() }}</p>
        </div>
    </div>

    <form method="GET" class="flex gap-3 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search product…" class="input w-60">
        <select name="filter" class="input w-44">
            <option value="">All Stock</option>
            <option value="low" @selected(request('filter')==='low')>Low Stock</option>
            <option value="out" @selected(request('filter')==='out')>Out of Stock</option>
        </select>
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800 text-slate-500 text-xs uppercase tracking-wider">
                    <th class="text-left px-5 py-3">Product</th>
                    <th class="text-left px-3 py-3">Category</th>
                    <th class="text-right px-3 py-3">Qty</th>
                    <th class="text-right px-3 py-3">Alert At</th>
                    <th class="text-right px-3 py-3">Cost Value</th>
                    <th class="text-center px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($stocks as $stock)
                <tr class="hover:bg-white/[0.02]">
                    <td class="px-5 py-3">
                        <p class="text-white">{{ $stock->product->name }}</p>
                        <p class="text-slate-600 text-xs font-mono">{{ $stock->product->barcode ?: '—' }}</p>
                    </td>
                    <td class="px-3 py-3 text-slate-400">{{ $stock->product->category ?: '—' }}</td>
                    <td class="px-3 py-3 text-right font-bold {{ $stock->isOutOfStock() ? 'text-red-400' : ($stock->isLow() ? 'text-amber-400' : 'text-white') }}">
                        {{ number_format($stock->quantity, 2) }}
                    </td>
                    <td class="px-3 py-3 text-right text-slate-500">{{ number_format($stock->low_stock_alert, 0) }}</td>
                    <td class="px-3 py-3 text-right text-slate-300">₵{{ number_format($stock->quantity * $stock->product->cost, 2) }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($stock->isOutOfStock())
                        <span class="badge bg-red-500/10 text-red-400">Out of Stock</span>
                        @elseif($stock->isLow())
                        <span class="badge bg-amber-500/10 text-amber-400">Low Stock</span>
                        @else
                        <span class="badge bg-green-500/10 text-green-400">OK</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-slate-600">No stock records</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-slate-800">{{ $stocks->links() }}</div>
    </div>
</div>
@endsection
