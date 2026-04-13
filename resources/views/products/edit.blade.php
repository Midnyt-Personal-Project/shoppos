@extends('layouts.app')
@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')
<div class="max-w-2xl space-y-5">
    <form method="POST" action="{{ route('products.update', $product) }}" class="space-y-5">
        @csrf @method('PUT')

        {{-- Product Details --}}
        <div class="card p-6 space-y-5">
            <h2 class="text-white font-semibold border-b border-slate-800 pb-3">Product Details</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-slate-400 text-xs mb-1 block">Product Name *</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="input">
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Barcode</label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}" class="input font-mono">
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="input">
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Category</label>
                    <input type="text" name="category" value="{{ old('category', $product->category) }}" class="input" list="cat-list">
                    <datalist id="cat-list">
                        <option>Beverages</option><option>Grains</option><option>Dairy</option>
                        <option>Bakery</option><option>Essentials</option><option>Electronics</option>
                        <option>Clothing</option><option>Health</option><option>Cosmetics</option>
                    </datalist>
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Unit *</label>
                    <select name="unit" class="input">
                        @foreach(['piece','kg','litre','bottle','can','pack','bag','tin','box','dozen','metre'] as $u)
                        <option value="{{ $u }}" @selected(old('unit', $product->unit) === $u)>{{ ucfirst($u) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="text-slate-400 text-xs mb-1 block">Description</label>
                    <textarea name="description" rows="2" class="input resize-none">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-white font-semibold border-b border-slate-800 pb-3">Pricing</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Selling Price (₵) *</label>
                    <input type="number" name="price" value="{{ old('price', $product->price) }}" required step="0.01" min="0" class="input">
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Cost Price (₵) *</label>
                    <input type="number" name="cost" value="{{ old('cost', $product->cost) }}" required step="0.01" min="0" class="input">
                </div>
            </div>
            <div class="flex items-center gap-3 pt-1">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       @checked(old('is_active', $product->is_active))
                       class="rounded border-slate-700 bg-slate-800 text-green-500 focus:ring-green-500">
                <label for="is_active" class="text-slate-300 text-sm">Product is active (visible in POS)</label>
            </div>
        </div>

        {{-- Branch Stock Levels --}}
        <div class="card p-6 space-y-4"
             x-data="{ removeModal: false, removeBranchId: null, removeBranchName: '', processing: false,
                 openRemove(bid, bname) { this.removeBranchId = bid; this.removeBranchName = bname; this.removeModal = true; },
                 async confirmRemove() {
                     this.processing = true;
                     const res = await fetch('/products/{{ $product->id }}/remove-branch', {
                         method: 'POST',
                         headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: JSON.stringify({ branch_id: this.removeBranchId })
                     });
                     const d = await res.json();
                     this.processing = false;
                     if (d.success) location.reload(); else alert(d.message);
                 }
             }">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h2 class="text-white font-semibold">Branch Stock Levels</h2>
                    <p class="text-slate-500 text-xs mt-0.5">Current stock per branch. Update alert levels here. Use the Restock button on the products list to add stock.</p>
                </div>
            </div>

            {{-- Existing branch stocks --}}
            @foreach($branches as $i => $branch)
            @php $stock = $stocksByBranch[$branch->id] ?? null; @endphp
            <div class="rounded-xl border p-4
                {{ $stock ? 'bg-slate-800/50 border-slate-700/50' : 'bg-slate-900/30 border-slate-800/30 opacity-60' }}">

                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full {{ $stock ? 'bg-green-500' : 'bg-slate-600' }}"></div>
                        <span class="text-sm font-medium {{ $stock ? 'text-white' : 'text-slate-500' }}">
                            {{ $branch->name }}
                        </span>
                        @if($branch->address)
                        <span class="text-slate-600 text-xs">— {{ $branch->address }}</span>
                        @endif
                    </div>

                    @if($stock)
                    {{-- Current stock badge --}}
                    <div class="flex items-center gap-3">
                        <span class="text-xs px-2 py-1 rounded font-mono font-bold
                            {{ $stock->isOutOfStock() ? 'bg-red-500/10 text-red-400'
                               : ($stock->isLow() ? 'bg-amber-500/10 text-amber-400'
                               : 'bg-green-500/10 text-green-400') }}">
                            {{ number_format($stock->quantity, 2) }} {{ $product->unit }}
                            @if($stock->isOutOfStock()) — Out of Stock
                            @elseif($stock->isLow()) — Low Stock
                            @endif
                        </span>
                        {{-- Remove from branch --}}
                        <button type="button"
                                @click="openRemove('{{ $branch->id }}', '{{ $branch->name }}')"
                                class="text-slate-600 hover:text-red-400 transition-colors text-xs"
                                title="Remove from this branch">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @else
                    <span class="text-slate-600 text-xs italic">Not assigned</span>
                    @endif
                </div>

                <input type="hidden" name="branch_stocks[{{ $i }}][branch_id]" value="{{ $branch->id }}">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">
                            Stock Quantity
                            @if(!$stock)<span class="text-slate-600">(will create entry)</span>@endif
                        </label>
                        <input type="number"
                               name="branch_stocks[{{ $i }}][quantity]"
                               value="{{ old("branch_stocks.{$i}.quantity", $stock?->quantity ?? 0) }}"
                               min="0" step="0.01" required
                               class="input {{ !$stock ? 'opacity-60' : '' }}"
                               placeholder="0">
                        @if($stock)
                        <p class="text-slate-600 text-[10px] mt-1">Edit to correct the count. For deliveries use the Restock button instead.</p>
                        @endif
                    </div>
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Low Stock Alert At</label>
                        <input type="number"
                               name="branch_stocks[{{ $i }}][low_stock_alert]"
                               value="{{ old("branch_stocks.{$i}.low_stock_alert", $stock?->low_stock_alert ?? 5) }}"
                               min="0" step="0.01" required
                               class="input {{ !$stock ? 'opacity-60' : '' }}">
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Remove from branch confirm modal --}}
            <div x-show="removeModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="card w-96 p-6 space-y-4" @click.outside="removeModal = false">
                    <h3 class="text-white font-semibold">Remove from Branch?</h3>
                    <p class="text-slate-400 text-sm">
                        Remove <strong class="text-white">{{ $product->name }}</strong> from
                        <strong class="text-white" x-text="removeBranchName"></strong>?
                        It will disappear from that branch's POS screen.
                    </p>
                    <div class="flex gap-3">
                        <button @click="removeModal = false" class="btn-secondary flex-1">Cancel</button>
                        <button @click="confirmRemove()" :disabled="processing"
                                class="btn-danger flex-1" x-text="processing ? 'Removing…' : 'Yes, Remove'"></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('products.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Update Product</button>
        </div>
    </form>
</div>
@endsection