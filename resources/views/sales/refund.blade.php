@extends('layouts.app')
@section('title','Process Return')
@section('page-title','Process Return')

@section('content')
<div class="max-w-2xl" x-data="refundSystem()">
    <div class="card p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-white font-semibold">Return Items</h2>
                <p class="text-slate-500 text-xs mt-1">Sale: <span class="font-mono text-green-400">{{ $sale->reference }}</span></p>
            </div>
            <a href="{{ route('sales.show', $sale) }}" class="btn-secondary text-xs">← Back</a>
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-800">
                    <th class="text-left py-2">Return?</th>
                    <th class="text-left py-2">Product</th>
                    <th class="text-right py-2">Sold</th>
                    <th class="text-right py-2">Already Returned</th>
                    <th class="text-right py-2">Return Qty</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach($sale->items as $item)
                @php $returnable = $item->quantity - $item->returned_quantity; @endphp
                <tr @class(['opacity-40' => $returnable <= 0])>
                    <td class="py-3 pr-3">
                        <input type="checkbox" :disabled="{{ $returnable <= 0 ? 'true' : 'false' }}"
                               @change="toggleItem({{ $item->id }}, $event.target.checked, {{ $returnable }})"
                               class="rounded border-slate-700 bg-slate-800 text-green-500">
                    </td>
                    <td class="py-3 text-white">{{ $item->product_name }}</td>
                    <td class="py-3 text-right text-slate-400">{{ number_format($item->quantity, 2) }}</td>
                    <td class="py-3 text-right text-slate-500">{{ number_format($item->returned_quantity, 2) }}</td>
                    <td class="py-3 text-right">
                        <template x-if="selected[{{ $item->id }}]">
                            <input type="number" x-model.number="selected[{{ $item->id }}]"
                                   min="0.01" max="{{ $returnable }}" step="0.01"
                                   class="w-20 input text-right py-1">
                        </template>
                        <template x-if="!selected[{{ $item->id }}]">
                            <span class="text-slate-600">—</span>
                        </template>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div x-show="Object.keys(selected).length > 0" class="bg-slate-800/50 rounded-xl p-4">
            <p class="text-slate-400 text-xs">Estimated refund amount</p>
            <p class="text-white font-bold text-xl" x-text="'₵' + refundTotal.toFixed(2)"></p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('sales.show', $sale) }}" class="btn-secondary">Cancel</a>
            <button @click="submitRefund()" :disabled="Object.keys(selected).length === 0 || processing"
                    class="btn-primary">
                <template x-if="!processing"><span>Confirm Return</span></template>
                <template x-if="processing"><span>Processing…</span></template>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function refundSystem() {
    const itemPrices = @json($sale->items->pluck('price', 'id'));
    return {
        selected: {},
        processing: false,
        get refundTotal() {
            return Object.entries(this.selected).reduce((sum, [id, qty]) => sum + (itemPrices[id] ?? 0) * qty, 0);
        },
        toggleItem(id, checked, max) {
            if (checked) this.selected[id] = max;
            else delete this.selected[id];
        },
        async submitRefund() {
            this.processing = true;
            const items = Object.entries(this.selected).map(([id, qty]) => ({ id: parseInt(id), return_qty: qty }));
            const res = await fetch('{{ route('pos.refund', $sale) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ items }),
            });
            const data = await res.json();
            if (data.success) {
                alert(`Return processed. Refund: ₵${parseFloat(data.refund_total).toFixed(2)}`);
                window.location = '{{ route('sales.show', $sale) }}';
            } else {
                alert('Error: ' + data.message);
                this.processing = false;
            }
        },
    };
}
</script>
@endpush