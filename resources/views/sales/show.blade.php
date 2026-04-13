@extends('layouts.app')
@section('title', 'Sale ' . $sale->reference)
@section('page-title', 'Sale Detail')

@section('content')
<div class="max-w-3xl space-y-5">

    <div class="flex items-center justify-between">
        <a href="{{ route('sales.index') }}" class="btn-secondary text-xs">← Back to Sales</a>
        <div class="flex gap-3">
            <a href="{{ route('pos.receipt', $sale) }}" target="_blank" class="btn-secondary text-xs">🖨️ Print Receipt</a>
            @if(auth()->user()->isManager() && $sale->status === 'completed')
            <a href="{{ route('sales.refund', $sale) }}" class="btn-danger text-xs">↩ Process Return</a>
            @endif
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5">
        {{-- Sale info --}}
        <div class="card p-5 space-y-3">
            <h2 class="text-white font-semibold text-sm">Sale Information</h2>
            @php $rows = [
                ['Reference', '<span class="font-mono text-green-400">' . $sale->reference . '</span>'],
                ['Date', $sale->created_at->format('d M Y, H:i')],
                ['Cashier', $sale->user->name],
                ['Branch', $sale->branch->name],
                ['Customer', $sale->customer?->name ?? 'Walk-in'],
                ['Status', '<span class="badge ' . (['completed'=>'bg-green-500/10 text-green-400','cancelled'=>'bg-red-500/10 text-red-400','refunded'=>'bg-slate-700 text-slate-400'][$sale->status] ?? '') . '">' . ucfirst($sale->status) . '</span>'],
            ]; @endphp
            @foreach($rows as [$label, $value])
            <div class="flex justify-between items-center py-1.5 border-b border-slate-800/60 last:border-0">
                <span class="text-slate-500 text-xs">{{ $label }}</span>
                <span class="text-sm">{!! $value !!}</span>
            </div>
            @endforeach
        </div>

        {{-- Payment info --}}
        <div class="card p-5 space-y-3">
            <h2 class="text-white font-semibold text-sm">Payment</h2>
            @foreach($sale->payments as $pay)
            <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                <span class="text-slate-400 text-sm">{{ $pay->methodLabel() }}</span>
                <span class="text-white text-sm font-medium">₵{{ number_format($pay->amount, 2) }}</span>
            </div>
            @endforeach
            <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                <span class="text-slate-400 text-sm">Total</span>
                <span class="text-white font-bold">₵{{ number_format($sale->total, 2) }}</span>
            </div>
            @if($sale->change > 0)
            <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                <span class="text-slate-400 text-sm">Change Given</span>
                <span class="text-green-400 text-sm">₵{{ number_format($sale->change, 2) }}</span>
            </div>
            @endif
            @if($sale->balance_due > 0)
            <div class="flex justify-between py-1.5">
                <span class="text-red-400 text-sm font-medium">Balance Due</span>
                <span class="text-red-400 font-bold">₵{{ number_format($sale->balance_due, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Items --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800">
            <h2 class="text-white font-semibold text-sm">Items ({{ $sale->items->count() }})</h2>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-800">
                    <th class="text-left px-5 py-2">Product</th>
                    <th class="text-right px-3 py-2">Price</th>
                    <th class="text-right px-3 py-2">Qty</th>
                    <th class="text-right px-5 py-2">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach($sale->items as $item)
                <tr @class(['opacity-50' => $item->is_returned])>
                    <td class="px-5 py-3">
                        {{ $item->product_name }}
                        @if($item->is_returned)<span class="badge bg-red-500/10 text-red-400 ml-2">Returned</span>@endif
                    </td>
                    <td class="px-3 py-3 text-right text-slate-400">₵{{ number_format($item->price, 2) }}</td>
                    <td class="px-3 py-3 text-right text-white">{{ number_format($item->quantity, 2) }}</td>
                    <td class="px-5 py-3 text-right text-green-400 font-medium">₵{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t border-slate-700">
                <tr><td colspan="3" class="px-5 py-3 text-right text-slate-400 text-sm">Subtotal</td><td class="px-5 py-3 text-right text-white">₵{{ number_format($sale->subtotal, 2) }}</td></tr>
                @if($sale->discount > 0)
                <tr><td colspan="3" class="px-5 py-1 text-right text-slate-400 text-sm">Discount</td><td class="px-5 py-1 text-right text-red-400">-₵{{ number_format($sale->discount, 2) }}</td></tr>
                @endif
                <tr><td colspan="3" class="px-5 py-3 text-right text-white font-bold">Total</td><td class="px-5 py-3 text-right text-green-400 font-bold text-lg">₵{{ number_format($sale->total, 2) }}</td></tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection