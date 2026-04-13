@extends('layouts.app')

@section('title', 'Sales | OmniPOS')
@section('page-title', 'Sales History')

@section('content')
<div class="space-y-6">
    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[150px]">
            <label class="block text-xs text-slate-500 mb-1">Reference / Customer</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reference or customer..." class="input w-full">
        </div>

        <div class="flex-1 min-w-[130px]">
            <label class="block text-xs text-slate-500 mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="input w-full">
        </div>

        <div class="flex-1 min-w-[130px]">
            <label class="block text-xs text-slate-500 mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="input w-full">
        </div>

        <div class="flex-1 min-w-[130px]">
            <label class="block text-xs text-slate-500 mb-1">Status</label>
            <select name="status" class="input w-full">
                <option value="">All Status</option>
                <option value="completed" @selected(request('status')==='completed')>Completed</option>
                <option value="cancelled" @selected(request('status')==='cancelled')>Cancelled</option>
                <option value="refunded"  @selected(request('status')==='refunded')>Refunded</option>
            </select>
        </div>

        <div class="flex-1 min-w-[130px]">
            <label class="block text-xs text-slate-500 mb-1">Payment Status</label>
            <select name="payment_status" class="input w-full">
                <option value="">All Payments</option>
                <option value="paid"    @selected(request('payment_status')==='paid')>Paid</option>
                <option value="partial" @selected(request('payment_status')==='partial')>Partial</option>
                <option value="unpaid"  @selected(request('payment_status')==='unpaid')>Unpaid</option>
            </select>
        </div>

        <div class="flex gap-2 items-center">
            <button type="submit" class="btn-primary whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filter
            </button>
            @if(request()->hasAny(['search','date_from','date_to','status','payment_status']))
            <a href="{{ route('sales.index') }}" class="btn-secondary whitespace-nowrap">Clear</a>
            @endif
        </div>
    </form>

    {{-- Sales Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-500 text-xs uppercase tracking-wider">
                        <th class="text-left px-4 py-3">Reference</th>
                        <th class="text-left px-4 py-3">Customer</th>
                        <th class="text-left px-4 py-3">Cashier</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="text-center px-4 py-3">Payment</th>
                        <th class="text-center px-4 py-3">Status</th>
                        <th class="text-right px-4 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-white/[0.02] cursor-pointer transition-colors" onclick="window.location='{{ route('sales.show', $sale) }}'">
                        <td class="px-4 py-3 font-mono text-xs text-green-400 whitespace-nowrap">{{ $sale->reference }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $sale->user->name }} .. <span> {{ $sale->user->branch->name }}</span></td>
                        <td class="px-4 py-3 text-right font-medium text-white whitespace-nowrap">₵{{ number_format($sale->total, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $paymentClass = match($sale->payment_status) {
                                    'paid'    => 'bg-green-500/10 text-green-400',
                                    'partial' => 'bg-amber-500/10 text-amber-400',
                                    default   => 'bg-red-500/10 text-red-400',
                                };
                            @endphp
                            <span class="badge {{ $paymentClass }} whitespace-nowrap">{{ ucfirst($sale->payment_status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $statusClass = match($sale->status) {
                                    'completed' => 'bg-green-500/10 text-green-400',
                                    'cancelled' => 'bg-red-500/10 text-red-400',
                                    'refunded'  => 'bg-slate-500/10 text-slate-400',
                                    default     => '',
                                };
                            @endphp
                            <span class="badge {{ $statusClass }} whitespace-nowrap">{{ ucfirst($sale->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-500 text-xs whitespace-nowrap">{{ $sale->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            No sales found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
        <div class="px-4 py-3 border-t border-slate-800">
            {{ $sales->links() }}
        </div>
        @endif
    </div>
</div>
@endsection