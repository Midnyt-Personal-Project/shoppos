@extends('layouts.app')
@section('title', $purchaseOrder->reference)
@section('page-title', 'Purchase Order')

@section('content')
<div class="max-w-5xl space-y-5"
     x-data="{
         receiveModal: false,
         rejectModal:  false,
         activeItem:   null,
         qtyReceived:  0,
         itemStatus:   'received',
         itemNotes:    '',
         processing:   false,
         rejectReason: '',

         openReceive(item) {
             this.activeItem  = item;
             this.qtyReceived = item.qty_requested;
             this.itemStatus  = 'received';
             this.itemNotes   = '';
             this.receiveModal = true;
         },

         async submitReceive() {
             this.processing = true;
             const res = await fetch('/purchase-orders/items/' + this.activeItem.id + '/receive', {
                 method:  'POST',
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                 body:    JSON.stringify({
                     quantity_received: this.qtyReceived,
                     item_status:       this.itemStatus,
                     notes:             this.itemNotes,
                 }),
             });
             const data = await res.json();
             this.processing = false;
             if (data.success) { this.receiveModal = false; location.reload(); }
             else alert(data.message);
         },
     }">

    {{-- Top bar --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase-orders.index') }}" class="btn-secondary text-xs">← Back</a>
            <span class="badge {{ $purchaseOrder->statusBadgeClass() }} text-sm px-3 py-1">
                {{ ucfirst($purchaseOrder->status) }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            {{-- Print --}}
            <a href="{{ route('purchase-orders.print', $purchaseOrder) }}" target="_blank" class="btn-secondary text-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"/>
                </svg>
                Print PO
            </a>

            {{-- Admin: Approve --}}
            @if(auth()->user()->isAdmin() && $purchaseOrder->isPending())
            <form method="POST" action="{{ route('purchase-orders.approve', $purchaseOrder) }}">
                @csrf
                <button type="submit" class="btn-primary text-xs">✓ Approve</button>
            </form>
            <button @click="rejectModal = true" class="btn-danger text-xs">✕ Reject</button>
            @endif

            {{-- Receive all at once --}}
            @if($purchaseOrder->isApproved() && $purchaseOrder->status !== 'received')
            <form method="POST" action="{{ route('purchase-orders.receiveAll', $purchaseOrder) }}"
                  onsubmit="return confirm('Mark all items as fully received and update stock?')">
                @csrf
                <button type="submit" class="btn-primary text-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Receive All
                </button>
            </form>
            @endif

            {{-- Delete (draft/rejected) --}}
            @if(in_array($purchaseOrder->status, ['draft','rejected']))
            <form method="POST" action="{{ route('purchase-orders.destroy', $purchaseOrder) }}"
                  onsubmit="return confirm('Delete this PO?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger text-xs">Delete</button>
            </form>
            @endif
        </div>
    </div>

    {{-- Info cards --}}
    <div class="grid md:grid-cols-2 gap-5">
        <div class="card p-5 space-y-2.5">
            <h2 class="text-white font-semibold text-sm border-b border-slate-800 pb-2">Order Info</h2>
            @foreach([
                ['Reference',    '<span class="font-mono text-green-400">' . $purchaseOrder->reference . '</span>'],
                ['Branch',        $purchaseOrder->branch->name],
                ['Requested By',  $purchaseOrder->creator->name],
                ['Date Created',  $purchaseOrder->created_at->format('d M Y, H:i')],
                ['Expected',      $purchaseOrder->expected_at?->format('d M Y') ?? '—'],
                ['Notes',         $purchaseOrder->notes ?: '—'],
            ] as [$label, $value])
            <div class="flex justify-between items-start">
                <span class="text-slate-500 text-xs">{{ $label }}</span>
                <span class="text-slate-300 text-sm text-right">{!! $value !!}</span>
            </div>
            @endforeach
        </div>

        <div class="card p-5 space-y-2.5">
            <h2 class="text-white font-semibold text-sm border-b border-slate-800 pb-2">Supplier & Approval</h2>
            @foreach([
                ['Supplier',      $purchaseOrder->supplier_name ?: '—'],
                ['Phone',         $purchaseOrder->supplier_phone ?: '—'],
                ['Approved By',   $purchaseOrder->approver?->name ?? '—'],
                ['Approved At',   $purchaseOrder->approved_at?->format('d M Y, H:i') ?? '—'],
                ['Items',         $purchaseOrder->items->count() . ' products'],
                ['Est. Total',    '₵' . number_format($purchaseOrder->totalCost(), 2)],
            ] as [$label, $value])
            <div class="flex justify-between items-start">
                <span class="text-slate-500 text-xs">{{ $label }}</span>
                <span class="text-slate-300 text-sm text-right">{{ $value }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Items table --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-white font-semibold text-sm">Order Items</h2>
            @if($purchaseOrder->isApproved())
            <p class="text-slate-500 text-xs">Click "Receive" on each item when goods arrive to update stock</p>
            @elseif($purchaseOrder->isPending())
            <p class="text-amber-400 text-xs">⏳ Waiting for admin approval before stock can be received</p>
            @elseif($purchaseOrder->status === 'rejected')
            <p class="text-red-400 text-xs">❌ This order was rejected</p>
            @endif
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-800">
                    <th class="text-left px-5 py-2">Product</th>
                    <th class="text-center px-3 py-2">Requested</th>
                    <th class="text-center px-3 py-2">Received</th>
                    <th class="text-right px-3 py-2">Unit Cost</th>
                    <th class="text-center px-3 py-2">Status</th>
                    @if($purchaseOrder->isApproved())
                    <th class="text-center px-5 py-2">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach($purchaseOrder->items as $item)
                <tr class="hover:bg-white/[0.02] transition-colors" @class(['opacity-60' => $item->status === 'received'])>
                    <td class="px-5 py-3">
                        <p class="text-white font-medium">{{ $item->product_name }}</p>
                        @if($item->notes)
                        <p class="text-slate-500 text-xs mt-0.5 italic">{{ $item->notes }}</p>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-center text-white font-mono">{{ number_format($item->quantity_requested, 0) }}</td>
                    <td class="px-3 py-3 text-center font-mono">
                        @if($item->quantity_received > 0)
                        <span class="{{ $item->quantity_received >= $item->quantity_requested ? 'text-green-400' : 'text-amber-400' }} font-bold">
                            {{ number_format($item->quantity_received, 0) }}
                        </span>
                        @else
                        <span class="text-slate-600">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-right text-slate-400">₵{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="px-3 py-3 text-center">
                        <span class="badge {{ $item->itemStatusClass() }}">{{ ucfirst($item->status) }}</span>
                    </td>
                    @if($purchaseOrder->isApproved())
                    <td class="px-5 py-3 text-center">
                        @if($item->status !== 'received')
                        <button @click="openReceive({
                                    id:            {{ $item->id }},
                                    name:          '{{ addslashes($item->product_name) }}',
                                    qty_requested: {{ $item->quantity_requested }},
                                })"
                                class="btn-primary text-xs py-1.5 px-3">
                            📦 Receive
                        </button>
                        @else
                        <span class="text-green-500 text-xs font-medium">✓ Done</span>
                        @endif
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Receive Item Modal --}}
    <div x-show="receiveModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-[420px] p-6 space-y-4" @click.outside="receiveModal = false">
            <div>
                <h3 class="text-white font-semibold">Receive Item</h3>
                <p class="text-slate-500 text-xs mt-0.5" x-text="activeItem?.name"></p>
            </div>

            <div class="bg-slate-800/50 rounded-lg px-4 py-2 text-xs text-slate-400">
                Quantity ordered: <span class="text-white font-bold" x-text="activeItem?.qty_requested"></span>
            </div>

            <div>
                <label class="text-slate-400 text-xs mb-1 block">Quantity Actually Received *</label>
                <input type="number" x-model.number="qtyReceived" min="0" step="0.01"
                       class="input text-lg font-bold text-center"
                       @input="itemStatus = qtyReceived <= 0 ? 'missing' : (qtyReceived >= (activeItem?.qty_requested ?? 0) ? 'received' : 'partial')">
                <p class="text-slate-600 text-xs mt-1">Enter 0 if this item was not delivered at all</p>
            </div>

            <div>
                <label class="text-slate-400 text-xs mb-2 block">Delivery Status</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['received' => ['✅','Received','green'], 'partial' => ['⚠️','Partial','amber'], 'missing' => ['❌','Missing','red']] as $val => [$icon, $label, $color])
                    <button type="button" @click="itemStatus = '{{ $val }}'"
                            :class="itemStatus === '{{ $val }}'
                                ? 'border-{{ $color }}-500 bg-{{ $color }}-500/10 text-{{ $color }}-400'
                                : 'border-slate-700 text-slate-500'"
                            class="py-2.5 rounded-lg text-xs font-medium border transition-all flex flex-col items-center gap-1">
                        <span>{{ $icon }}</span>
                        <span>{{ $label }}</span>
                    </button>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="text-slate-400 text-xs mb-1 block">Notes (optional)</label>
                <input type="text" x-model="itemNotes" class="input"
                       placeholder="e.g. Damaged box, short supply, wrong item...">
            </div>

            <div class="pt-1 bg-green-500/5 border border-green-500/10 rounded-lg px-4 py-2.5">
                <p class="text-green-400 text-xs">
                    <span x-text="qtyReceived"></span> units will be added to
                    <strong>{{ $purchaseOrder->branch->name }}</strong>'s stock immediately.
                </p>
            </div>

            <div class="flex gap-3">
                <button @click="receiveModal = false" class="btn-secondary flex-1">Cancel</button>
                <button @click="submitReceive()" :disabled="processing"
                        class="btn-primary flex-1"
                        x-text="processing ? 'Updating stock…' : 'Confirm & Update Stock'">
                </button>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div x-show="rejectModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-96 p-6 space-y-4" @click.outside="rejectModal = false">
            <h3 class="text-white font-semibold">Reject Purchase Order</h3>
            <form method="POST" action="{{ route('purchase-orders.reject', $purchaseOrder) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Reason for rejection (optional)</label>
                    <textarea name="reason" rows="3" class="input resize-none"
                              placeholder="e.g. Budget exceeded, wrong supplier, not needed yet..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="rejectModal = false" class="btn-secondary flex-1">Cancel</button>
                    <button type="submit" class="btn-danger flex-1">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection