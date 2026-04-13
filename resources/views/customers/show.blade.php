@extends('layouts.app')
@section('title', $customer->name)
@section('page-title', $customer->name)

@section('content')
<div class="space-y-5 max-w-4xl" x-data="{ showRepay: false, repayAmount: 0 }">

    <div class="flex items-center justify-between">
        <a href="{{ route('customers.index') }}" class="btn-secondary text-xs">← Customers</a>
        @if($customer->outstanding_balance > 0)
        <button @click="showRepay = true; repayAmount = {{ $customer->outstanding_balance }}" class="btn-primary">
            Record Repayment
        </button>
        @endif
    </div>

    <div class="grid md:grid-cols-3 gap-4">
        <div class="card p-5">
            <p class="text-slate-500 text-xs">Outstanding Debt</p>
            <p class="text-2xl font-bold {{ $customer->outstanding_balance > 0 ? 'text-red-400' : 'text-green-400' }} mt-1">
                ₵{{ number_format($customer->outstanding_balance, 2) }}
            </p>
        </div>
        <div class="card p-5">
            <p class="text-slate-500 text-xs">Total Spent (All Time)</p>
            <p class="text-2xl font-bold text-white mt-1">₵{{ number_format($customer->totalSpent(), 2) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-slate-500 text-xs">Total Sales</p>
            <p class="text-2xl font-bold text-white mt-1">{{ $customer->sales->count() }}</p>
        </div>
    </div>

    <div class="card p-5 space-y-3">
        <h2 class="text-white font-semibold text-sm">Customer Info</h2>
        @foreach([['Phone', $customer->phone], ['Email', $customer->email], ['Address', $customer->address], ['Credit Limit', '₵' . number_format($customer->credit_limit, 2)]] as [$label, $value])
        <div class="flex justify-between py-1.5 border-b border-slate-800/60 last:border-0">
            <span class="text-slate-500 text-xs">{{ $label }}</span>
            <span class="text-slate-300 text-sm">{{ $value ?: '—' }}</span>
        </div>
        @endforeach
    </div>

    {{-- Recent sales --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800"><h2 class="text-white font-semibold text-sm">Recent Sales</h2></div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-800">
                    <th class="text-left px-5 py-2">Reference</th>
                    <th class="text-right px-3 py-2">Total</th>
                    <th class="text-center px-3 py-2">Payment</th>
                    <th class="text-right px-5 py-2">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($recentSales as $sale)
                <tr class="hover:bg-white/[0.02] cursor-pointer" onclick="window.location='{{ route('sales.show', $sale) }}'">
                    <td class="px-5 py-3 font-mono text-xs text-green-400">{{ $sale->reference }}</td>
                    <td class="px-3 py-3 text-right text-white font-medium">₵{{ number_format($sale->total, 2) }}</td>
                    <td class="px-3 py-3 text-center">
                        @php $pc = ['paid'=>'bg-green-500/10 text-green-400','partial'=>'bg-amber-500/10 text-amber-400','unpaid'=>'bg-red-500/10 text-red-400']; @endphp
                        <span class="badge {{ $pc[$sale->payment_status] ?? '' }}">{{ ucfirst($sale->payment_status) }}</span>
                    </td>
                    <td class="px-5 py-3 text-right text-slate-500 text-xs">{{ $sale->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-8 text-center text-slate-600">No sales yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Repayment Modal --}}
    <div x-show="showRepay" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-80 p-6 space-y-4" @click.outside="showRepay = false">
            <h3 class="text-white font-semibold">Record Debt Repayment</h3>
            <p class="text-slate-400 text-xs">Current balance: <span class="text-red-400 font-bold">₵{{ number_format($customer->outstanding_balance, 2) }}</span></p>
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Amount Paid</label>
                <input type="number" x-model.number="repayAmount" min="0.01" max="{{ $customer->outstanding_balance }}" step="0.01" class="input">
            </div>
            <div class="flex gap-3">
                <button @click="showRepay = false" class="btn-secondary flex-1">Cancel</button>
                <button @click="
                    fetch('{{ route('customers.repay', $customer) }}', {
                        method: 'POST',
                        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ amount: repayAmount })
                    }).then(r => r.json()).then(d => {
                        if(d.success) location.reload(); else alert(d.message);
                    })" class="btn-primary flex-1">Confirm</button>
            </div>
        </div>
    </div>

</div>
@endsection