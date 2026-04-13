@extends('layouts.app')
@section('title', 'Add Product')
@section('page-title', 'Add Product')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('products.store') }}" class="space-y-5"
          x-data="{
              scanMode: false,
              stream: null,
              async startScan() {
                  try {
                      this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                      document.getElementById('barcodeCamera').srcObject = this.stream;
                      this.scanMode = true;
                  } catch(e) { alert('Camera not available'); }
              },
              stopScan() {
                  if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
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
                        <input type="text" name="barcode" id="barcodeInput" value="{{ old('barcode') }}"
                               class="input flex-1 font-mono" placeholder="Scan or type barcode">
                        <button type="button" @click="scanMode ? stopScan() : startScan()"
                                :class="scanMode ? 'border-green-500 text-green-400 bg-green-500/10' : 'border-slate-700 text-slate-400'"
                                class="px-3 py-2 rounded-lg border bg-slate-800 hover:border-green-500 transition-all shrink-0" title="Camera scan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
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
                    <input type="text" name="category" value="{{ old('category') }}" class="input" list="cat-list" placeholder="e.g. Beverages">
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
                        <option value="{{ $u }}" @selected(old('unit') === $u)>{{ ucfirst($u) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="text-slate-400 text-xs mb-1 block">Description</label>
                    <textarea name="description" rows="2" class="input resize-none" placeholder="Optional description">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-white font-semibold border-b border-slate-800 pb-3">Pricing</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Selling Price (₵) *</label>
                    <input type="number" name="price" value="{{ old('price') }}" required step="0.01" min="0" class="input">
                    @error('price')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Cost Price (₵) *</label>
                    <input type="number" name="cost" value="{{ old('cost') }}" required step="0.01" min="0" class="input">
                </div>
            </div>
            <div class="flex items-center gap-3 pt-1">
                <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true))
                       class="rounded border-slate-700 bg-slate-800 text-green-500 focus:ring-green-500">
                <label for="is_active" class="text-slate-300 text-sm">Product is active (visible in POS)</label>
            </div>
        </div>

        {{-- ── Branch Stock Assignment ──────────────────────────────────── --}}
        <div class="card p-6 space-y-4" x-data="{ rows: {{ json_encode(old('branch_stocks', [])) }} }">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h2 class="text-white font-semibold">Branch Stock Assignment</h2>
                    <p class="text-slate-500 text-xs mt-0.5">Assign this product to one or more branches and set the opening stock for each.</p>
                </div>
            </div>

            {{-- Branch rows --}}
            <div class="space-y-3">
                @foreach($branches as $i => $branch)
                @php
                    $oldBs  = collect(old('branch_stocks', []))->firstWhere('branch_id', $branch->id);
                @endphp
                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-2 h-2 rounded-full bg-green-500"></div>
                        <span class="text-white text-sm font-medium">{{ $branch->name }}</span>
                        @if($branch->address)
                        <span class="text-slate-500 text-xs">— {{ $branch->address }}</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-slate-400 text-xs mb-1 block">Opening Stock *</label>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="branch_stocks[{{ $i }}][branch_id]" value="{{ $branch->id }}">
                                <input type="number"
                                       name="branch_stocks[{{ $i }}][quantity]"
                                       value="{{ $oldBs['quantity'] ?? 0 }}"
                                       min="0" step="0.01" required class="input">
                            </div>
                        </div>
                        <div>
                            <label class="text-slate-400 text-xs mb-1 block">Low Stock Alert At</label>
                            <input type="number"
                                   name="branch_stocks[{{ $i }}][low_stock_alert]"
                                   value="{{ $oldBs['low_stock_alert'] ?? 5 }}"
                                   min="0" step="0.01" required class="input">
                        </div>
                    </div>
                </div>
                @endforeach

                @if($branches->isEmpty())
                <div class="text-center py-6 text-slate-600">
                    <p class="text-sm">No active branches found.</p>
                    <a href="{{ route('branches.index') }}" class="text-green-500 text-xs hover:underline">Add a branch first →</a>
                </div>
                @endif
            </div>

            <p class="text-slate-600 text-xs">Set opening stock to <strong class="text-slate-500">0</strong> for branches where this product is not currently stocked. You can restock later.</p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('products.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save Product</button>
        </div>
    </form>
</div>
@endsection