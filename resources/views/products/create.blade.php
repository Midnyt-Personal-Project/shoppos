@extends('layouts.app')
@section('title', 'Add Product')
@section('page-title', 'Add Product')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="space-y-5"
        x-data="{
            scanMode: false,
            stream: null,
            type: '{{ old('type', 'product') }}',
            serviceBranches: {{ empty(old('service_branches')) ? '[]' : json_encode(old('service_branches')) }},
            async startScan() {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                    document.getElementById('barcodeCamera').srcObject = this.stream;
                    this.scanMode = true;
                } catch (e) { alert('Camera not available'); }
            },
            stopScan() {
                if (this.stream) {
                    this.stream.getTracks().forEach(t => t.stop());
                    this.stream = null;
                }
                this.scanMode = false;
            }
        }">
        @csrf

        {{-- Product Details --}}
        <div class="card p-6 space-y-5">
            <h2 class="text-white font-semibold border-b border-slate-800 pb-3">Product Details</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-slate-400 text-xs mb-1 block">Product Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="input" placeholder="e.g. Coca-Cola 500ml">
                    @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Barcode</label>
                    <div class="flex gap-2">
                        <input type="text" name="barcode" id="barcodeInput" value="{{ old('barcode') }}" class="input flex-1 font-mono" placeholder="Scan or type barcode">
                        <button type="button" @click="scanMode ? stopScan() : startScan()"
                            :class="scanMode ? 'border-green-500 text-green-400 bg-green-500/10' : 'border-slate-700 text-slate-400'"
                            class="px-3 py-2 rounded-lg border bg-slate-800 hover:border-green-500 transition-all shrink-0" title="Camera scan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                    <div x-show="scanMode" x-cloak class="mt-2">
                        <video id="barcodeCamera" class="w-full h-28 rounded-lg bg-black object-cover" autoplay playsinline></video>
                    </div>
                </div>

                <div>
                    <label class="text-slate-400 text-xs mb-1 block">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku') }}" class="input" placeholder="Internal code (optional)">
                </div>

                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Category</label>
                    <input type="text" name="category" value="{{ old('category') }}" class="input" list="cat-list" placeholder="e.g. Antibiotics">
                    <datalist id="cat-list">
                        @foreach($allCategories as $cat)
                            <option>{{ $cat }}</option>
                        @endforeach
                    </datalist>
                </div>

                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Unit *</label>
                    <input type="text" name="unit" value="{{ old('unit') }}" required class="input" list="unit-list" placeholder="e.g. Tablet">
                    <datalist id="unit-list">
                        @foreach($allUnits as $unit)
                            <option>{{ $unit }}</option>
                        @endforeach
                    </datalist>
                </div>

                <div class="col-span-2">
                    <label class="text-slate-400 text-xs mb-1 block">Description</label>
                    <textarea name="description" rows="2" class="input resize-none" placeholder="Optional description">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Product Image</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif" class="input">
                    <p class="text-slate-500 text-xs mt-1">Optional product image (max 2MB). Will be displayed in POS and sales receipts.</p>
                </div>

                <div id="imagePreview" class="mt-2 hidden">
                    <img src="#" alt="Image Preview" class="w-24 h-24 object-cover rounded">
                </div>

                {{-- Product Type & Price Override --}}
                <div class="col-span-2">
                    <div class="space-y-4">
                        <label class="text-slate-400 text-xs mb-1 block">Product Type</label>
                        <div class="flex items-center gap-6 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="product" x-model="type" class="rounded border-slate-700 bg-slate-800 text-green-500">
                                <span class="text-slate-300 text-sm">📦 Physical Product (tracks stock)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="service" x-model="type" class="rounded border-slate-700 bg-slate-800 text-green-500">
                                <span class="text-slate-300 text-sm">⚙️ Service (no stock, can be price‑editable)</span>
                            </label>
                        </div>

                        <div x-show="type === 'service'" x-cloak class="mt-3 pt-2">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="allow_price_override" value="1"
                                    @checked(old('allow_price_override', false))
                                    class="rounded border-slate-700 bg-slate-800 text-green-500">
                                <span class="text-slate-300 text-sm">💰 Allow cashier to change price at POS (for variable fees)</span>
                            </label>
                            <p class="text-slate-500 text-xs mt-1 ml-6">Enable this for services like delivery, special meals, or any item where the price is set per sale.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pricing (with auto-calc) --}}
        <div class="card p-6 space-y-4" x-data="pricingCalculator()" x-init="init()">
            <h2 class="text-white font-semibold border-b border-slate-800 pb-3">Pricing</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Cost Price (₵) *</label>
                    <input type="number" name="cost" x-model="cost" @input="updateFromCost()" required step="0.01" min="0" class="input">
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Profit Margin (%)</label>
                    <input type="number" x-model="margin" @input="updateFromMargin()" step="0.01" min="0" class="input" placeholder="e.g. 25">
                    <p class="text-slate-500 text-xs mt-1">Optional: Set margin % to auto-calculate price</p>
                </div>
            </div>
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Selling Price (₵) *</label>
                <input type="number" name="price" x-model="price" @input="updateFromPrice()" required step="0.01" min="0" class="input">
                @error('price')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-3 pt-1">
                <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true))
                    class="rounded border-slate-700 bg-slate-800 text-green-500 focus:ring-green-500">
                <label for="is_active" class="text-slate-300 text-sm">Product is active (visible in POS)</label>
            </div>
        </div>

        {{-- Branch Assignment (Product: stock fields, Service: checkboxes) --}}
        <div class="card p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h2 class="text-white font-semibold">Branch Assignment</h2>
                    <p class="text-slate-500 text-xs mt-0.5">
                        For physical products, set opening stock per branch. For services, just choose which branches can offer the service.
                    </p>
                </div>
            </div>

            {{-- Physical product stock assignment --}}
            <div x-show="type === 'product'" x-cloak>
                <div class="space-y-3">
                    @foreach ($branches as $i => $branch)
                        @php
                            $oldBs = collect(old('branch_stocks', []))->firstWhere('branch_id', $branch->id);
                        @endphp
                        <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                <span class="text-white text-sm font-medium">{{ $branch->name }}</span>
                                @if ($branch->address)
                                    <span class="text-slate-500 text-xs">— {{ $branch->address }}</span>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-slate-400 text-xs mb-1 block">Stock Quantity *</label>
                                    <input type="hidden" name="branch_stocks[{{ $i }}][branch_id]" value="{{ $branch->id }}">
                                    <input type="number" name="branch_stocks[{{ $i }}][quantity]"
                                           value="{{ $oldBs['quantity'] ?? 0 }}" min="0" step="0.01" required
                                           class="input" :disabled="type !== 'product'">
                                </div>
                                <div>
                                    <label class="text-slate-400 text-xs mb-1 block">Low Stock Alert</label>
                                    <input type="number" name="branch_stocks[{{ $i }}][low_stock_alert]"
                                           value="{{ $oldBs['low_stock_alert'] ?? 5 }}" min="0" step="0.01" required
                                           class="input" :disabled="type !== 'product'">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="text-slate-600 text-xs mt-3">Set opening stock to <strong class="text-slate-500">0</strong> for branches where this product is not currently stocked. You can restock later.</p>
            </div>

            {{-- Service branch availability (just checkboxes) --}}
            <div x-show="type === 'service'" x-cloak>
                <div class="space-y-2">
                    <label class="text-slate-400 text-sm block mb-2">Select branches where this service will be available:</label>
                    @foreach ($branches as $branch)
                        <label class="flex items-center gap-3 cursor-pointer py-2">
                            <input type="checkbox" name="service_branches[]" value="{{ $branch->id }}"
                                x-model="serviceBranches"
                                class="rounded border-slate-700 bg-slate-800 text-green-500 focus:ring-green-500">
                            <span class="text-slate-300 text-sm">{{ $branch->name }}</span>
                            @if ($branch->address)
                                <span class="text-slate-500 text-xs">— {{ $branch->address }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                <p class="text-slate-500 text-xs mt-3">The service will appear in the POS only for the branches you select here.</p>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('products.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save Product</button>
        </div>
    </form>
</div>

<script>
    // Image preview
    document.querySelector('input[name="image"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = document.querySelector('#imagePreview img');
                img.src = event.target.result;
                document.getElementById('imagePreview').classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        } else {
            document.getElementById('imagePreview').classList.add('hidden');
        }
    });

    function pricingCalculator() {
        return {
            cost: {{ old('cost', 0) }},
            price: {{ old('price', 0) }},
            margin: 0,
            init() {
                if (this.cost > 0 && this.price > 0) {
                    this.margin = ((this.price - this.cost) / this.cost) * 100;
                    this.margin = Math.round(this.margin * 100) / 100;
                }
            },
            updateFromCost() {
                if (this.margin !== undefined && this.margin !== null && this.margin !== '' && !isNaN(this.margin)) {
                    this.updateFromMargin();
                }
            },
            updateFromMargin() {
                if (this.margin === undefined || this.margin === null || this.margin === '' || isNaN(this.margin)) return;
                const marginValue = parseFloat(this.margin);
                if (!isNaN(marginValue) && this.cost && this.cost > 0) {
                    let newPrice = this.cost * (1 + marginValue / 100);
                    this.price = Math.round(newPrice * 100) / 100;
                }
            },
            updateFromPrice() {
                if (this.cost && this.cost > 0 && this.price !== undefined && !isNaN(this.price)) {
                    let newMargin = ((this.price - this.cost) / this.cost) * 100;
                    this.margin = Math.round(newMargin * 100) / 100;
                }
            }
        }
    }

    // Submit handler for services - FIXED VERSION
    document.querySelector('form').addEventListener('submit', function(e) {
        const type = document.querySelector('input[name="type"]:checked')?.value;
        
        if (type === 'service') {
            console.log('Form submitted as SERVICE');
            
            // 1. REMOVE ALL branch_stocks fields (both hidden branch_id and the quantity/alert inputs)
            const branchStocksToRemove = document.querySelectorAll('input[name*="branch_stocks"]');
            console.log('Removing ' + branchStocksToRemove.length + ' branch_stocks fields');
            branchStocksToRemove.forEach(el => {
                el.remove();
            });
            
            // 2. Get checked service branches
            const checkedBranches = Array.from(document.querySelectorAll('input[name="service_branches[]"]:checked'))
                .map(cb => cb.value);
            
            console.log('Service branches checked:', checkedBranches);
            
            // 3. Create branch_stocks hidden inputs ONLY for the checked branches
            checkedBranches.forEach((branchId, idx) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `branch_stocks[${idx}][branch_id]`;
                input.value = branchId;
                document.querySelector('form').appendChild(input);
                console.log('Added branch_stocks[' + idx + '][branch_id] = ' + branchId);
            });
            
            console.log('Form ready to submit with service branches only');
        } else if (type === 'product') {
            console.log('Form submitted as PRODUCT');
            // For products, remove service_branches checkboxes so they don't get submitted
            document.querySelectorAll('input[name="service_branches[]"]').forEach(el => {
                el.remove();
            });
        }
    });
</script>
@endsection