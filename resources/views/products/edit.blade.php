@extends('layouts.app')
@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')
    <div class="max-w-2xl space-y-5">
        <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')

            <div class="card p-6 space-y-5">
                <h2 class="text-white font-semibold border-b border-slate-800 pb-3">Product Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="text-slate-400 text-xs mb-1 block">Product Name *</label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                            class="input">
                    </div>
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Barcode</label>
                        <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                            class="input font-mono">
                    </div>
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="input">
                    </div>
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Category</label>
                        <input type="text" name="category" value="{{ old('category', $product->category) }}"
                            class="input" list="cat-list">
                        <datalist id="cat-list">
                            @foreach ($allCategories ?? [] as $cat)
                                <option>{{ $cat }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Unit *</label>
                        <input type="text" name="unit" value="{{ old('unit', $product->unit) }}" class="input"
                            list="unit-list" required>
                        <datalist id="unit-list">
                            @foreach ($allUnits ?? [] as $unit)
                                <option>{{ $unit }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-span-2">
                        <label class="text-slate-400 text-xs mb-1 block">Description</label>
                        <textarea name="description" rows="2" class="input resize-none">{{ old('description', $product->description) }}</textarea>
                    </div>
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Product Image</label>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif"
                            class="input">
                        @if ($product->image)
                            <div class="mt-2">
                                <img src="{{ Storage::url($product->image) }}" class="w-24 h-24 object-cover rounded">
                            </div>
                        @endif
                    </div>

                    {{-- Product Type & Price Override --}}
                    <div class="col-span-2">
                        <div class="space-y-4">
                            <label class="text-slate-400 text-xs mb-1 block">Product Type</label>
                            <div class="flex items-center gap-6 pt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="type" value="product" @checked(old('type', $product->type) === 'product')
                                        class="rounded border-slate-700 bg-slate-800 text-green-500">
                                    <span class="text-slate-300 text-sm">📦 Physical Product</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="type" value="service" @checked(old('type', $product->type) === 'service')
                                        class="rounded border-slate-700 bg-slate-800 text-green-500">
                                    <span class="text-slate-300 text-sm">⚙️ Service</span>
                                </label>
                            </div>

                            <div x-data="{ isService: '{{ old('type', $product->type) }}' === 'service' }">
                                <div x-show="isService" x-cloak class="mt-3 pt-2">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" name="allow_price_override" value="1"
                                            @checked(old('allow_price_override', $product->allow_price_override))
                                            class="rounded border-slate-700 bg-slate-800 text-green-500">
                                        <span class="text-slate-300 text-sm">
                                            💰 Allow cashier to change price at POS
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pricing (no auto-calc needed, but we can keep simple fields) --}}
            <div class="card p-6 space-y-4">
                <h2 class="text-white font-semibold border-b border-slate-800 pb-3">Pricing</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Selling Price (₵) *</label>
                        <input type="number" name="price" value="{{ old('price', $product->price) }}" required
                            step="0.01" min="0" class="input">
                    </div>
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Cost Price (₵) *</label>
                        <input type="number" name="cost" value="{{ old('cost', $product->cost) }}" required
                            step="0.01" min="0" class="input">
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-1">
                    <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $product->is_active))
                        class="rounded border-slate-700 bg-slate-800 text-green-500 focus:ring-green-500">
                    <label for="is_active" class="text-slate-300 text-sm">Product is active (visible in POS)</label>
                </div>
            </div>

            {{-- Branch Stock Assignment --}}
            {{-- Branch Stock / Service Assignment --}}
            <div class="card p-6 space-y-4" x-data="{
                type: '{{ $product->type }}',
                serviceBranches: {{ json_encode($product->stocks->pluck('branch_id')->toArray()) }}
            }">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h2 class="text-white font-semibold">Branch Assignment</h2>
                </div>

                {{-- Physical product stock levels --}}
                <div x-show="type === 'product'" x-cloak>
                    @foreach ($branches as $i => $branch)
                        @php
                            $stock = $stocksByBranch[$branch->id] ?? null;
                        @endphp
                        <div class="rounded-xl border p-4 bg-slate-800/50 border-slate-700/50 mb-3">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                    <span class="text-sm font-medium text-white">{{ $branch->name }}</span>
                                </div>
                                @if ($stock)
                                    <span
                                        class="text-xs px-2 py-1 rounded font-mono
                            {{ $stock->isOutOfStock()
                                ? 'bg-red-500/10 text-red-400'
                                : ($stock->isLow()
                                    ? 'bg-amber-500/10 text-amber-400'
                                    : 'bg-green-500/10 text-green-400') }}">
                                        {{ number_format($stock->quantity, 2) }} {{ $product->unit }}
                                    </span>
                                @endif
                            </div>
                            <input type="hidden" name="branch_stocks[{{ $i }}][branch_id]"
                                value="{{ $branch->id }}">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-slate-400 text-xs mb-1 block">Stock Quantity</label>
                                    <input type="number" name="branch_stocks[{{ $i }}][quantity]"
                                        value="{{ old("branch_stocks.{$i}.quantity", $stock?->quantity ?? 0) }}"
                                        min="0" step="0.01" class="input">
                                </div>
                                <div>
                                    <label class="text-slate-400 text-xs mb-1 block">Low Stock Alert At</label>
                                    <input type="number" name="branch_stocks[{{ $i }}][low_stock_alert]"
                                        value="{{ old("branch_stocks.{$i}.low_stock_alert", $stock?->low_stock_alert ?? 5) }}"
                                        min="0" step="0.01" class="input">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Service branch availability --}}
                <div x-show="type === 'service'" x-cloak>
                    <div class="space-y-2">
                        <label class="text-slate-400 text-sm block mb-2">Select branches where this service is
                            available:</label>
                        @foreach ($branches as $branch)
                            <label class="flex items-center gap-3 cursor-pointer py-2">
                                <input type="checkbox" name="service_branches[]" value="{{ $branch->id }}"
                                    x-model="serviceBranches"
                                    class="rounded border-slate-700 bg-slate-800 text-green-500 focus:ring-green-500">
                                <span class="text-slate-300 text-sm">{{ $branch->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('products.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Update Product</button>
            </div>
        </form>
    </div>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
    const type = document.querySelector('input[name="type"]:checked')?.value;
    if (type === 'service') {
        const serviceBranches = document.querySelectorAll('input[name="service_branches[]"]:checked');
        document.querySelectorAll('input[name^="branch_stocks["]').forEach(el => el.remove());
        serviceBranches.forEach((cb, idx) => {
            const branchId = cb.value;
            const container = document.createElement('div');
            container.innerHTML = `
                <input type="hidden" name="branch_stocks[${idx}][branch_id]" value="${branchId}">
                <input type="hidden" name="branch_stocks[${idx}][quantity]" value="">
                <input type="hidden" name="branch_stocks[${idx}][low_stock_alert]" value="">
            `;
            document.querySelector('form').appendChild(container.firstChild);
            document.querySelector('form').appendChild(container.children[1]);
            document.querySelector('form').appendChild(container.children[2]);
        });
    }
});
    </script>
@endsection
