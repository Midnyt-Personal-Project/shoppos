@extends('layouts.app')
@section('title','New Supply Request')
@section('page-title','New Supply Request')

@section('content')
<div class="max-w-4xl"
     x-data="{
         rows: [],
         search: '',
         searchResults: [],
         searching: false,

         async searchProducts() {
             if (this.search.length < 1) { this.searchResults = []; return; }
             this.searching = true;
             const res = await fetch('/pos/search?q=' + encodeURIComponent(this.search), {
                 headers: { 'X-Requested-With': 'XMLHttpRequest' }
             });
             this.searchResults = await res.json();
             this.searching = false;
         },

         addProduct(product) {
             const exists = this.rows.find(r => r.product_id === product.id);
             if (exists) {
                 exists.quantity_requested += 1;
             } else {
                 this.rows.push({
                     product_id:          product.id,
                     product_name:        product.name,
                     current_stock:       product.stock,
                     unit:                product.unit,
                     unit_cost:           product.cost,
                     quantity_requested:  1,
                 });
             }
             this.search = '';
             this.searchResults = [];
         },

         removeRow(index) {
             this.rows.splice(index, 1);
         },
     }">

    <form method="POST" action="{{ route('purchase-orders.store') }}" class="space-y-5">
        @csrf

        {{-- Header details --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-white font-semibold border-b border-slate-800 pb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Supply Request Details
            </h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Supplier Name</label>
                    <input type="text" name="supplier_name" value="{{ old('supplier_name') }}"
                           class="input" placeholder="e.g. Accra Drug Wholesale Ltd">
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Supplier Phone</label>
                    <input type="text" name="supplier_phone" value="{{ old('supplier_phone') }}"
                           class="input" placeholder="e.g. 0244000000">
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Expected Delivery Date</label>
                    <input type="date" name="expected_at" value="{{ old('expected_at') }}" class="input">
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                           class="input" placeholder="Optional notes for admin">
                </div>
            </div>
        </div>

        {{-- Product search + items --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-white font-semibold border-b border-slate-800 pb-3">Items to Order</h2>

            {{-- Search --}}
            <div class="relative">
                <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" x-model="search"
                       @input.debounce.200ms="searchProducts()"
                       @keydown.escape="searchResults = []"
                       placeholder="Search product by name or barcode to add…"
                       class="input pl-10">

                {{-- Dropdown results --}}
                <div x-show="searchResults.length > 0" x-cloak
                     class="absolute z-20 w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl shadow-xl overflow-hidden">
                    <template x-for="product in searchResults" :key="product.id">
                        <div @click="addProduct(product)"
                             class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 cursor-pointer border-b border-slate-700/50 last:border-0">
                            <div>
                                <p class="text-white text-sm font-medium" x-text="product.name"></p>
                                <p class="text-slate-500 text-xs font-mono" x-text="product.barcode || '—'"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-green-400 text-sm font-bold" x-text="'₵' + parseFloat(product.price).toFixed(2)"></p>
                                <p class="text-xs" :class="product.stock <= 0 ? 'text-red-400' : 'text-slate-500'"
                                   x-text="'Stock: ' + product.stock"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="searching" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="w-4 h-4 text-slate-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            </div>

            {{-- Items table --}}
            <div x-show="rows.length > 0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-800">
                            <th class="text-left py-2">Product</th>
                            <th class="text-center py-2 w-24">In Stock</th>
                            <th class="text-center py-2 w-36">Qty to Order</th>
                            <th class="text-center py-2 w-32">Unit Cost (₵)</th>
                            <th class="text-center py-2 w-12"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <template x-for="(row, index) in rows" :key="index">
                            <tr>
                                <td class="py-3 pr-3">
                                    <input type="hidden" :name="'items[' + index + '][product_id]'" :value="row.product_id">
                                    <p class="text-white text-sm font-medium" x-text="row.product_name"></p>
                                    <p class="text-slate-500 text-xs" x-text="row.unit"></p>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="text-sm font-mono"
                                          :class="row.current_stock <= 0 ? 'text-red-400' : (row.current_stock <= 5 ? 'text-amber-400' : 'text-slate-400')"
                                          x-text="row.current_stock"></span>
                                </td>
                                <td class="py-3 px-2">
                                    <input type="number"
                                           :name="'items[' + index + '][quantity_requested]'"
                                           x-model.number="row.quantity_requested"
                                           min="0.01" step="0.01" required
                                           class="input text-center">
                                </td>
                                <td class="py-3 px-2">
                                    <input type="number"
                                           :name="'items[' + index + '][unit_cost]'"
                                           x-model.number="row.unit_cost"
                                           min="0" step="0.01"
                                           class="input text-right">
                                </td>
                                <td class="py-3 text-center">
                                    <button type="button" @click="removeRow(index)"
                                            class="text-slate-600 hover:text-red-400 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="border-t border-slate-700">
                        <tr>
                            <td colspan="2" class="py-3 text-slate-500 text-sm" x-text="rows.length + ' item(s)'"></td>
                            <td colspan="2" class="py-3 text-right text-white font-medium text-sm">
                                Est. Total:
                                <span class="text-green-400 font-bold"
                                      x-text="'₵' + rows.reduce((s, r) => s + (r.unit_cost * r.quantity_requested), 0).toFixed(2)">
                                </span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Empty state --}}
            <div x-show="rows.length === 0" x-cloak
                 class="text-center py-10 text-slate-600">
                <svg class="w-10 h-10 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="text-sm">Search for products above to add them to this order</p>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('purchase-orders.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" :disabled="rows.length === 0"
                    :class="rows.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                    class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Submit Supply Request
            </button>
        </div>
    </form>
</div>
@endsection