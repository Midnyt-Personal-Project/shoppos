@extends('layouts.app')
@section('title','Purchase Orders')
@section('page-title','Purchase Orders')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <p class="text-slate-400 text-sm">Manage supply requests and goods receiving</p>
            @if($pendingCount > 0)
            <span class="badge bg-amber-500/15 text-amber-400 border border-amber-500/20">
                {{ $pendingCount }} pending approval
            </span>
            @endif
        </div>
        <a href="{{ route('purchase-orders.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Supply Request
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search PO reference…" class="input w-52">
        <select name="status" class="input w-40">
            <option value="">All Status</option>
            @foreach(['draft'=>'Draft','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','partial'=>'Partial','received'=>'Received'] as $val => $label)
            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        @if(auth()->user()->isAdmin() && $branches->count() > 1)
        <select name="branch_id" class="input w-44">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
            <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @endif
        <button type="submit" class="btn-secondary">Filter</button>
        @if(request()->hasAny(['status','branch_id','search']))
        <a href="{{ route('purchase-orders.index') }}" class="btn-secondary">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800 text-slate-500 text-xs uppercase tracking-wider">
                    <th class="text-left px-5 py-3">Reference</th>
                    <th class="text-left px-3 py-3">Branch</th>
                    <th class="text-left px-3 py-3">Supplier</th>
                    <th class="text-left px-3 py-3">Requested By</th>
                    <th class="text-center px-3 py-3">Items</th>
                    <th class="text-center px-3 py-3">Status</th>
                    <th class="text-right px-5 py-3">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($orders as $order)
                @if($order->shop_id == auth()->user()->shop_id && $order->branch_id == auth()->user()->branch_id) {{-- Ensure only orders from the user's shop and branch are shown --}}
                <tr class="hover:bg-white/[0.02] cursor-pointer transition-colors"
                    onclick="window.location='{{ route('purchase-orders.show', $order) }}'">
                    <td class="px-5 py-3">
                        <span class="font-mono text-xs text-green-400">{{ $order->reference }}</span>
                        @if($order->expected_at)
                        <p class="text-slate-600 text-xs mt-0.5">Expected {{ $order->expected_at->format('d M') }}</p>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-slate-400 text-xs">{{ $order->branch->name }}</td>
                    <td class="px-3 py-3 text-slate-300">{{ $order->supplier_name ?: '—' }}</td>
                    <td class="px-3 py-3 text-slate-400">{{ $order->creator->name }}</td>
                    <td class="px-3 py-3 text-center text-white font-medium">{{ $order->items->count() }}</td>
                    <td class="px-3 py-3 text-center">
                        <span class="badge {{ $order->statusBadgeClass() }}">{{ ucfirst($order->status) }}</span>
                    </td>
                    <td class="px-5 py-3 text-right text-slate-500 text-xs">{{ $order->created_at->format('d M Y') }}</td>
                </tr>

                @elseif ($order->shop_id == auth()->user()->shop_id && auth()->user()->isAdmin() && request('branch_id') == null) {{-- Admins can see all orders from their shop --}}
                    <tr class="hover:bg-white/[0.02] cursor-pointer transition-colors"
                    onclick="window.location='{{ route('purchase-orders.show', $order) }}'">
                    <td class="px-5 py-3">
                        <span class="font-mono text-xs text-green-400">{{ $order->reference }}</span>
                        @if($order->expected_at)
                        <p class="text-slate-600 text-xs mt-0.5">Expected {{ $order->expected_at->format('d M') }}</p>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-slate-400 text-xs">{{ $order->branch->name }}</td>
                    <td class="px-3 py-3 text-slate-300">{{ $order->supplier_name ?: '—' }}</td>
                    <td class="px-3 py-3 text-slate-400">{{ $order->creator->name }}</td>
                    <td class="px-3 py-3 text-center text-white font-medium">{{ $order->items->count() }}</td>
                    <td class="px-3 py-3 text-center">
                        <span class="badge {{ $order->statusBadgeClass() }}">{{ ucfirst($order->status) }}</span>
                    </td>
                    <td class="px-5 py-3 text-right text-slate-500 text-xs">{{ $order->created_at->format('d M Y') }}</td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center text-slate-600">
                        No purchase orders yet.
                        <a href="{{ route('purchase-orders.create') }}" class="text-green-500 hover:underline ml-1">Create one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-slate-800">{{ $orders->links() }}</div>
    </div>

</div>
@endsection