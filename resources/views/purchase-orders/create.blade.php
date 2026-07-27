@extends('layouts.app')
@section('title','New Supply Request')
@section('page-title','New Supply Request')

@section('content')
<div class="max-w-4xl" id="createPurchaseOrder">

    <form method="POST" action="{{ route('purchase-orders.store') }}" class="space-y-5" id="poForm">
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
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Supplier Dropdown --}}
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Supplier</label>
                    <select id="supplierSelect" class="input" onchange="handleSupplierChange(this)">
                        <option value="">— Select Supplier —</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier['name'] }}" data-phone="{{ $supplier['phone'] }}">
                                {{ $supplier['name'] }}
                            </option>
                        @endforeach
                        <option value="new">➕ Add New Supplier</option>
                    </select>
                </div>

                {{-- Supplier Name (editable when "new" selected) --}}
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Supplier Name</label>
                    <input type="text" name="supplier_name" id="supplierName" value="{{ old('supplier_name') }}"
                           class="input" placeholder="Enter supplier name" readonly>
                </div>

                {{-- Supplier Phone --}}
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Supplier Phone</label>
                    <input type="text" name="supplier_phone" id="supplierPhone" value="{{ old('supplier_phone') }}"
                           class="input" placeholder="Enter supplier phone" readonly>
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
                <input type="text" id="productSearch"
                       placeholder="Search product by name or barcode to add…"
                       class="input pl-10">
                <div id="searchResults" class="absolute z-20 w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl shadow-xl overflow-hidden hidden"></div>
                <div id="searchSpinner" class="absolute right-3 top-1/2 -translate-y-1/2 hidden">
                    <svg class="w-4 h-4 text-slate-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            </div>

            {{-- Items table --}}
            <div id="itemsContainer">
                <div id="emptyState" class="text-center py-10 text-slate-600">
                    <svg class="w-10 h-10 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-sm">Search for products above to add them to this order</p>
                </div>
                <div id="itemsTableWrapper" class="hidden">
                    <table class="w-full text-sm" id="itemsTable">
                        <thead>
                            <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-800">
                                <th class="text-left py-2">Product</th>
                                <th class="text-center py-2 w-24">In Stock</th>
                                <th class="text-center py-2 w-36">Qty to Order</th>
                                <th class="text-center py-2 w-32">Unit Cost (₵)</th>
                                <th class="text-center py-2 w-12"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody" class="divide-y divide-slate-800"></tbody>
                        <tfoot id="itemsFoot" class="border-t border-slate-700 hidden">
                            <tr>
                                <td colspan="2" class="py-3 text-slate-500 text-sm" id="itemCount"></td>
                                <td colspan="2" class="py-3 text-right text-white font-medium text-sm">
                                    Est. Total:
                                    <span id="estimatedTotal" class="text-green-400 font-bold">₵0.00</span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('purchase-orders.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" id="submitBtn" class="btn-primary" disabled>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Submit Supply Request
            </button>
        </div>
    </form>
</div>

<script>
    (function() {
        // ── Supplier Dropdown Logic ───────────────────────────────────────
        function handleSupplierChange(select) {
            const nameField = document.getElementById('supplierName');
            const phoneField = document.getElementById('supplierPhone');

            if (select.value === 'new') {
                nameField.readOnly = false;
                nameField.value = '';
                phoneField.readOnly = false;
                phoneField.value = '';
                nameField.focus();
            } else if (select.value) {
                nameField.readOnly = true;
                phoneField.readOnly = true;
                nameField.value = select.value;
                const phone = select.options[select.selectedIndex].dataset.phone || '';
                phoneField.value = phone;
            } else {
                nameField.readOnly = true;
                phoneField.readOnly = true;
                nameField.value = '';
                phoneField.value = '';
            }
        }

        // ── Product Search & Items ────────────────────────────────────────
        let rows = [];
        let searchTimeout = null;
        let isSearching = false;

        const searchInput = document.getElementById('productSearch');
        const searchResults = document.getElementById('searchResults');
        const searchSpinner = document.getElementById('searchSpinner');
        const itemsBody = document.getElementById('itemsBody');
        const itemsFoot = document.getElementById('itemsFoot');
        const itemCount = document.getElementById('itemCount');
        const estimatedTotal = document.getElementById('estimatedTotal');
        const emptyState = document.getElementById('emptyState');
        const itemsTableWrapper = document.getElementById('itemsTableWrapper');
        const submitBtn = document.getElementById('submitBtn');

        function renderItems() {
            if (rows.length === 0) {
                emptyState.classList.remove('hidden');
                itemsTableWrapper.classList.add('hidden');
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                return;
            }

            emptyState.classList.add('hidden');
            itemsTableWrapper.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');

            let html = '';
            rows.forEach((row, index) => {
                html += `
                    <tr>
                        <td class="py-3 pr-3">
                            <input type="hidden" name="items[${index}][product_id]" value="${row.product_id}">
                            <p class="text-white text-sm font-medium">${escapeHtml(row.product_name)}</p>
                            <p class="text-slate-500 text-xs">${escapeHtml(row.unit || '')}</p>
                        </td>
                        <td class="py-3 text-center">
                            <span class="text-sm font-mono ${row.current_stock <= 0 ? 'text-red-400' : (row.current_stock <= 5 ? 'text-amber-400' : 'text-slate-400')}">
                                ${row.current_stock}
                            </span>
                        </td>
                        <td class="py-3 px-2">
                            <input type="number"
                                   name="items[${index}][quantity_requested]"
                                   value="${row.quantity_requested}"
                                   min="0.01" step="0.01" required
                                   class="input text-center qty-input"
                                   data-index="${index}">
                        </td>
                        <td class="py-3 px-2">
                            <input type="number"
                                   name="items[${index}][unit_cost]"
                                   value="${row.unit_cost}"
                                   min="0" step="0.01"
                                   class="input text-right cost-input"
                                   data-index="${index}">
                        </td>
                        <td class="py-3 text-center">
                            <button type="button" class="text-slate-600 hover:text-red-400 transition-colors remove-btn" data-index="${index}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                `;
            });

            itemsBody.innerHTML = html;

            document.querySelectorAll('.qty-input').forEach(input => {
                input.addEventListener('change', function() {
                    const idx = parseInt(this.dataset.index);
                    rows[idx].quantity_requested = parseFloat(this.value) || 0;
                    updateTotals();
                });
                input.addEventListener('input', function() {
                    const idx = parseInt(this.dataset.index);
                    rows[idx].quantity_requested = parseFloat(this.value) || 0;
                    updateTotals();
                });
            });

            document.querySelectorAll('.cost-input').forEach(input => {
                input.addEventListener('change', function() {
                    const idx = parseInt(this.dataset.index);
                    rows[idx].unit_cost = parseFloat(this.value) || 0;
                    updateTotals();
                });
                input.addEventListener('input', function() {
                    const idx = parseInt(this.dataset.index);
                    rows[idx].unit_cost = parseFloat(this.value) || 0;
                    updateTotals();
                });
            });

            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = parseInt(this.dataset.index);
                    rows.splice(idx, 1);
                    renderItems();
                });
            });

            updateTotals();
            itemCount.textContent = rows.length + ' item(s)';
            itemsFoot.classList.remove('hidden');
        }

        function updateTotals() {
            let total = 0;
            rows.forEach(row => {
                total += (row.unit_cost || 0) * (row.quantity_requested || 0);
            });
            estimatedTotal.textContent = '₵' + total.toFixed(2);
        }

        // ── Search ──────────────────────────────────────────────────────────
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            if (query.length < 1) {
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
                return;
            }
            searchTimeout = setTimeout(() => performSearch(query), 300);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
                this.value = '';
            }
        });

        async function performSearch(query) {
            if (isSearching) return;
            isSearching = true;
            searchSpinner.classList.remove('hidden');
            searchResults.innerHTML = '';

            try {
                const res = await fetch('/pos/search?q=' + encodeURIComponent(query), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.length === 0) {
                    searchResults.innerHTML = '<div class="px-4 py-3 text-slate-500 text-sm">No products found</div>';
                    searchResults.classList.remove('hidden');
                    return;
                }
                let html = '';
                data.forEach(product => {
                    html += `
                        <div class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 cursor-pointer border-b border-slate-700/50 last:border-0 search-result"
                             data-product='${JSON.stringify(product)}'>
                            <div>
                                <p class="text-white text-sm font-medium">${escapeHtml(product.name)}</p>
                                <p class="text-slate-500 text-xs font-mono">${escapeHtml(product.barcode || '—')}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-green-400 text-sm font-bold">₵${parseFloat(product.price).toFixed(2)}</p>
                                <p class="text-xs ${product.stock <= 0 ? 'text-red-400' : 'text-slate-500'}">
                                    Stock: ${product.stock}
                                </p>
                            </div>
                        </div>
                    `;
                });
                searchResults.innerHTML = html;
                searchResults.classList.remove('hidden');

                document.querySelectorAll('.search-result').forEach(el => {
                    el.addEventListener('click', function() {
                        const product = JSON.parse(this.dataset.product);
                        addProduct(product);
                        searchResults.classList.add('hidden');
                        searchResults.innerHTML = '';
                        searchInput.value = '';
                    });
                });
            } catch (err) {
                console.error('Search error:', err);
            } finally {
                isSearching = false;
                searchSpinner.classList.add('hidden');
            }
        }

        function addProduct(product) {
            const exists = rows.find(r => r.product_id === product.id);
            if (exists) {
                exists.quantity_requested += 1;
            } else {
                rows.push({
                    product_id: product.id,
                    product_name: product.name,
                    current_stock: product.stock || 0,
                    unit: product.unit || '',
                    unit_cost: product.cost || 0,
                    quantity_requested: 1,
                });
            }
            renderItems();
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ── Init ───────────────────────────────────────────────────────────
        renderItems();

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#productSearch') && !e.target.closest('#searchResults')) {
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
            }
        });

        // Expose supplier handler to global scope for inline onclick
        window.handleSupplierChange = handleSupplierChange;
    })();
</script>
@endsection