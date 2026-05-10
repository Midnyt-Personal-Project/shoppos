@extends('layouts.app')

@section('title', 'POS | OmniPOS')

@push('styles')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        #qr-reader {
            background: #000;
            border-radius: 1rem;
            overflow: hidden;
        }

        #qr-reader video {
            border-radius: 1rem;
        }

        #scannerOverlay {
            transition: opacity 0.2s ease;
        }

        #scannerOverlay.hidden {
            display: none;
        }

        @media (max-width: 768px) {
            .cart-two-column {
                flex-direction: column !important;
            }

            .cart-items-col,
            .cart-payment-col {
                width: 100% !important;
            }
        }

        /* Custom scrollbar */
        .cart-items-list::-webkit-scrollbar {
            width: 4px;
        }

        .cart-items-list::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 10px;
        }

        .cart-items-list::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 10px;
        }
    </style>
@endpush

@section('content')
    <div class="space-y-6 w-full min-w-0">

        <!-- Search & Scan -->
        <div class="flex gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input id="searchInput" class="input pl-10 py-3" placeholder="Search name or barcode..." type="text">
            </div>
            <button id="scanButton" class="btn-primary flex flex-col items-center justify-center w-14 h-14 rounded-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4M12 12h4.01M20 12h4M4 12H2m-2 0h4M12 20h4M4 20h4" />
                </svg>
                <span class="text-[10px] font-bold uppercase mt-1">Scan</span>
            </button>
        </div>

        <!-- Category Filters -->
        <!-- Added max-w-2xl and mx-auto to keep it centered and contained -->
        <div class="overflow-hidden w-full max-w-2xl mx-auto">
            <div class="overflow-x-auto overflow-y-hidden no-scrollbar">
                <div class="flex gap-2 whitespace-nowrap px-2">
                    <button
                        class="filter-btn flex-shrink-0 px-5 py-2 rounded-full bg-brand-600 text-white font-semibold text-sm">
                        All
                    </button>
                    @foreach ($categories as $cat)
                        <button
                            class="filter-btn flex-shrink-0 px-5 py-2 rounded-full bg-surface-card text-slate-300 font-medium text-sm hover:bg-surface-card/80 transition-colors">
                            {{ $cat }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Product Grid -->
        <div id="productGrid"
            class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-6 gap-4 pb-8 min-w-0"></div>

        <!-- Floating Cart Button -->
        <div class="fixed bottom-24 right-6 z-40">
            <button id="cartButton"
                class="w-14 h-14 bg-brand-600 text-white rounded-full shadow-lg flex items-center justify-center relative hover:bg-brand-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21h6" />
                </svg>
                <div id="cartBadge"
                    class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                    0</div>
            </button>
        </div>

        <!-- Cart Modal – Two Column Layout -->
        <div id="cartModal" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
            <div class="card w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="flex justify-between items-center p-4 border-b border-surface-border">
                    <div class="flex items-center gap-2">
                        <button id="closeCartModal" class="p-2 rounded-lg hover:bg-surface-card transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <h2 class="text-lg font-bold text-white">Current Order</h2>
                    </div>
                    <span
                        class="text-xs text-brand-400 bg-brand-600/20 px-3 py-1 rounded-full">#{{ rand(1000, 9999) }}</span>
                </div>

                <!-- Two columns body -->
                <div class="flex flex-1 overflow-hidden cart-two-column">
                    <!-- LEFT COLUMN: Cart Items -->
                    <div class="w-full  flex flex-col border-r border-surface-border">
                        <div class="p-3 border-b border-surface-border bg-surface-card/30">
                            <h3 class="text-white font-semibold text-sm">Order Items</h3>
                        </div>
                        <div id="cartItemsList" class="flex-1 overflow-y-auto p-3 space-y-3 cart-items-list">
                            <div class="text-center text-slate-400 py-8">Cart is empty</div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Payment & Totals -->
                    <div class="w-1/2 flex flex-col p-4 space-y-4">
                        <!-- Totals -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Discount</span>
                                <span id="subtotal"
                                    class="text-white font-semibold">{{ auth()->user()->shop->currency_symbol }}0.00</span>
                            </div>
                            <div class="flex items-center gap-2 bg-surface-card rounded-lg px-3 py-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <input id="discountInput"
                                    class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-white placeholder:text-slate-500"
                                    placeholder="Discount ({{ auth()->user()->shop->currency_symbol }})" type="number"
                                    min="0" step="0.01" value="0">
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Tax</span>
                                <span id="taxAmount"
                                    class="text-white font-semibold">{{ auth()->user()->shop->currency_symbol }}0.00</span>
                            </div>
                            <div class="flex justify-between items-end pt-2 border-t border-surface-border">
                                <span class="text-slate-400 uppercase text-xs font-bold">Grand Total</span>
                                <h2 id="grandTotal" class="text-3xl font-bold text-white">
                                    {{ auth()->user()->shop->currency_symbol }}0.00</h2>
                            </div>
                        </div>

                        <!-- Customer selector -->
                        <div>
                            <select id="customerSelect" class="input w-full text-sm">
                                <option value="">— Walk-in Customer —</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        data-balance="{{ $customer->outstanding_balance }}">
                                        {{ $customer->name }}
                                        @if ($customer->outstanding_balance > 0)
                                            (Owes
                                            {{ auth()->user()->shop->currency_symbol }}{{ number_format($customer->outstanding_balance, 2) }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div id="customerBalanceDisplay" class="text-xs text-slate-400 mt-1 hidden">
                                Outstanding debt: <span id="customerBalanceAmount"
                                    class="text-amber-400 font-semibold"></span>
                            </div>
                        </div>

                        <!-- Amount paid -->
                        <div>
                            <label class="text-slate-400 text-xs mb-1 block">Amount Paid</label>
                            <div class="relative">
                                <span
                                    class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400">{{ auth()->user()->shop->currency_symbol }}</span>
                                <input type="number" id="amountPaidInput" step="0.01" min="0"
                                    class="input pl-7 w-full" value="0.00">
                            </div>
                            <p id="amountPaidHint" class="text-xs text-slate-500 mt-1"></p>
                        </div>

                        <!-- Payment method buttons -->
                        <div class="grid grid-cols-3 gap-2">
                            <button onclick="setPaymentMethod('cash')" id="pm-cash"
                                class="pay-method py-2 rounded-lg text-xs font-medium border transition-all bg-brand-600 text-white border-brand-600">Cash</button>
                            <button onclick="setPaymentMethod('mobile_money')" id="pm-mobile_money"
                                class="pay-method py-2 rounded-lg text-xs font-medium border transition-all bg-slate-800 text-slate-400 border-slate-700">MoMo</button>
                            <button onclick="setPaymentMethod('card')" id="pm-card"
                                class="pay-method py-2 rounded-lg text-xs font-medium border transition-all bg-slate-800 text-slate-400 border-slate-700">Card</button>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex flex-col gap-3 pt-2">
                            <button id="collectPaymentBtn" class="btn-primary w-full py-3 justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                                COLLECT PAYMENT
                            </button>
                            <div class="flex gap-3">
                                <button id="receiptButton" class="btn-secondary flex-1 justify-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z" />
                                    </svg>
                                    Receipt
                                </button>
                                <button
                                    onclick="document.getElementById('cartModal').classList.add('hidden');document.getElementById('customerModal').classList.remove('hidden')"
                                    class="btn-secondary flex-1 justify-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    Customer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sale Complete Modal -->
        <div id="saleCompleteModal" class="fixed inset-0 z-50 hidden bg-black/60 flex items-center justify-center p-4">
            <div class="card w-96 p-8 text-center">
                <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-white text-xl font-bold mb-1">Sale Complete!</h2>
                <p class="text-slate-400 text-sm mb-1" id="completedRef"></p>
                <div id="changeDisplay" class="bg-green-500/10 rounded-xl p-4 my-4 hidden">
                    <p class="text-slate-400 text-xs">Change to give</p>
                    <p class="text-green-400 text-3xl font-bold" id="changeAmount"></p>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-6">
                    <button id="printReceiptBtn" class="btn-secondary justify-center py-2.5">🖨️ Print Receipt</button>
                    <button onclick="newSale()" class="btn-primary justify-center py-2.5">New Sale →</button>
                </div>
            </div>
        </div>

        <!-- Add Customer Modal -->
        <div id="customerModal" class="fixed inset-0 z-50 hidden bg-black/60 flex items-center justify-center p-4">
            <div class="card w-96 p-6">
                <h3 class="text-white font-semibold mb-4">Add New Customer</h3>
                <form id="addCustomerForm" class="space-y-3">
                    @csrf
                    <div><label class="text-slate-400 text-xs mb-1 block">Name *</label><input type="text"
                            name="name" required class="input"></div>
                    <div><label class="text-slate-400 text-xs mb-1 block">Phone</label><input type="tel"
                            name="phone" class="input"></div>
                    <div><label class="text-slate-400 text-xs mb-1 block">Email</label><input type="email"
                            name="email" class="input"></div>
                    <div class="flex gap-3 mt-4">
                        <button type="button"
                            onclick="document.getElementById('customerModal').classList.add('hidden');document.getElementById('cartModal').classList.remove('hidden')"
                            class="btn-secondary flex-1">Cancel</button>
                        <button type="submit" class="btn-primary flex-1">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Scanner Overlay -->
        <div id="scannerOverlay"
            class="fixed inset-0 z-50 bg-black/90 hidden flex flex-col items-center justify-center p-4">
            <div class="relative w-full max-w-md mx-auto">
                <div id="qr-reader" style="width: 100%;"></div>
                <button id="closeScanner"
                    class="absolute top-2 right-2 p-2 rounded-full bg-white/20 hover:bg-white/30 transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mt-6 w-full max-w-md">
                <div class="bg-surface-card rounded-lg p-4">
                    <p class="text-slate-300 text-sm mb-2 text-center">Having trouble? Enter barcode manually:</p>
                    <div class="flex gap-2">
                        <input type="text" id="manualBarcode" class="input flex-1" placeholder="Enter barcode number"
                            autocomplete="off">
                        <button id="submitBarcode" class="btn-primary whitespace-nowrap">Add</button>
                    </div>
                </div>
            </div>
            <p class="text-white text-sm mt-4">Position barcode/QR code in front of camera</p>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        const CURRENCY = '{{ auth()->user()->shop->currency_symbol }}';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const products = @json($products);
        const TAX_RATES = @json($taxRates);

        function calculateTax(subtotal, discount) {
            const taxable = Math.max(0, subtotal - discount);
            let totalTax = 0;
            for (let tax of TAX_RATES) {
                totalTax += taxable * (tax.rate / 100);
            }
            return totalTax;
        }

        function imgSrc(product) {
    // If product has an uploaded image, use it
    if (product.image) {
        return '/storage/' + product.image;
    }
    // For service products without an image, use service.png
    if (product.type === 'service') {
        return '/service.png';
    }
    // Default image for regular products
    return '/default.jpeg';
}

        function imgError(el) {
            el.onerror = null;
            el.src = '/default.jpg';
        }

        // Cart state
        var cart = [];
        var paymentMethod = 'cash';
        var lastSaleId = null;
        var customersList = {};

        document.querySelectorAll('#customerSelect option').forEach(opt => {
            if (opt.value) customersList[opt.value] = parseFloat(opt.dataset.balance || 0);
        });

        function getProductById(id) {
            return products.find(p => p.id === id);
        }

        // Stock-aware add to cart
        function addToCart(productId) {
            var product = getProductById(productId);
            if (!product) {
                alert('Product not found. It may have been deactivated. Please refresh the page.');
                return;
            }
            // For services, no stock check
            if (product.type === 'service') {
                var existing = cart.find(i => i.productId === productId);
                if (existing) existing.quantity++;
                else cart.push({
                    productId: productId,
                    quantity: 1
                });
                updateCartUI();
                saveCart();
                playBeep();
                return;
            }
            // For products
            var currentStock = product.stock;
            if (currentStock <= 0) {
                alert('"' + product.name + '" is out of stock.');
                return;
            }
            var existing = cart.find(i => i.productId === productId);
            var newQuantity = existing ? existing.quantity + 1 : 1;
            if (newQuantity > currentStock) {
                alert('Cannot add more than available stock. Only ' + currentStock + ' left.');
                return;
            }
            if (existing) existing.quantity++;
            else cart.push({
                productId: productId,
                quantity: 1
            });
            updateCartUI();
            saveCart();
            playBeep();
        }

        function updateQuantity(productId, delta) {
            var product = getProductById(productId);
            if (!product) return;
            var item = cart.find(i => i.productId === productId);
            if (!item) return;
            var newQty = item.quantity + delta;
            if (newQty <= 0) {
                cart = cart.filter(i => i.productId !== productId);
            } else if (product.type === 'product' && newQty > product.stock) {
                alert('Cannot exceed available stock. Max ' + product.stock + ' allowed.');
                return;
            } else {
                item.quantity = newQty;
            }
            updateCartUI();
            saveCart();
        }

        function removeItem(productId) {
            cart = cart.filter(i => i.productId !== productId);
            updateCartUI();
            saveCart();
        }

        function calculateTotals() {
            var discount = parseFloat(document.getElementById('discountInput').value || 0);
            var subtotal = 0;
            var itemCount = 0;
            cart.forEach(item => {
                var p = getProductById(item.productId);
                if (p) {
                    var itemPrice = (p.allow_price_override && item.customPrice) ? item.customPrice : p.price;
                    subtotal += itemPrice * item.quantity;
                    itemCount += item.quantity;
                }
            });
            var tax = calculateTax(subtotal, discount);
            var grandTotal = Math.max(0, subtotal - discount + tax);
            return {
                subtotal,
                discount,
                tax,
                grandTotal,
                itemCount
            };
        }

       function updateCartUI() {
    try {
        var t = calculateTotals();
        document.getElementById('cartBadge').innerText = t.itemCount;
        document.getElementById('subtotal').innerText = CURRENCY + t.subtotal.toFixed(2);
        document.getElementById('taxAmount').innerText = CURRENCY + t.tax.toFixed(2);
        document.getElementById('grandTotal').innerText = CURRENCY + t.grandTotal.toFixed(2);

        var amountInput = document.getElementById('amountPaidInput');
        var customerId = document.getElementById('customerSelect').value;
        if (amountInput) {
            if (!customerId) {
                amountInput.value = t.grandTotal.toFixed(2);
                amountInput.readOnly = true;
            } else {
                amountInput.readOnly = false;
                if (parseFloat(amountInput.value) === 0) {
                    amountInput.value = t.grandTotal.toFixed(2);
                }
            }
        }
        updateAmountPaidHint();

        if (cart.length === 0) {
            document.getElementById('cartItemsList').innerHTML = '<div class="text-center text-slate-400 py-8">Cart is empty</div>';
            return;
        }

        var html = '';
        for (var idx = 0; idx < cart.length; idx++) {
            var item = cart[idx];
            var p = getProductById(item.productId);
            if (!p) continue;

            // Determine the effective price
            var effectivePrice = p.price;
            if (p.allow_price_override) {
                if (item.customPrice !== undefined && item.customPrice !== null && !isNaN(item.customPrice)) {
                    effectivePrice = item.customPrice;
                } else {
                    // If customPrice is not set, use the product's price (and store it)
                    effectivePrice = p.price;
                    item.customPrice = effectivePrice;
                }
            }
            var lineTotal = effectivePrice * item.quantity;

            var priceHtml = '';
            if (p.allow_price_override) {
                priceHtml = `
                    <div class="flex items-center gap-1 justify-end">
                        <span class="text-slate-400 text-xs">${CURRENCY}</span>
                        <input type="number" step="0.01" class="editable-price w-20 text-right bg-slate-700 border border-slate-600 rounded px-1 py-0.5 text-sm text-white"
                               data-product-id="${p.id}" value="${effectivePrice.toFixed(2)}">
                        <span class="text-slate-400 text-xs">ea</span>
                    </div>
                `;
            } else {
                priceHtml = `<p class="text-brand-400 text-xs">${CURRENCY}${p.price.toFixed(2)} ea</p>`;
            }

            html += `<div class="bg-surface-card p-3 rounded-xl">
                <div class="flex gap-3">
                    <img class="w-12 h-12 rounded-lg object-cover bg-surface-DEFAULT flex-shrink-0" src="${imgSrc(p)}" alt="${escHtml(p.name)}" onerror="imgError(this)">
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-white text-sm">${escHtml(p.name)}</h3>
                        <p class="text-xs text-slate-500 font-mono">${p.barcode || '—'}</p>
                        <div class="flex items-center justify-between mt-1">
                            <div class="flex items-center gap-2">
                                <button onclick="updateQuantity(${p.id},-1)" class="w-6 h-6 rounded-md bg-slate-700 hover:bg-slate-600 text-white flex items-center justify-center">−</button>
                                <span class="text-white font-bold w-6 text-center text-sm">${item.quantity}</span>
                                <button onclick="updateQuantity(${p.id},1)" class="w-6 h-6 rounded-md bg-slate-700 hover:bg-slate-600 text-white flex items-center justify-center">+</button>
                            </div>
                            <div class="text-right">
                                ${priceHtml}
                                <p class="text-white font-bold text-sm">${CURRENCY}${lineTotal.toFixed(2)}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end mt-1">
                    <button onclick="removeItem(${p.id})" class="text-red-400 hover:text-red-300 text-xs">✕ Remove</button>
                </div>
            </div>`;
        }
        document.getElementById('cartItemsList').innerHTML = html;
        attachPriceEditListeners();
    } catch (err) {
        console.error('Error in updateCartUI:', err);
        alert('An error occurred while updating the cart. Please refresh the page.');
    }
}

        function attachPriceEditListeners() {
            document.querySelectorAll('.editable-price').forEach(input => {
                input.removeEventListener('input', handlePriceChange);
                input.addEventListener('input', handlePriceChange);
            });
        }

        function handlePriceChange(e) {
            const productId = parseInt(e.target.dataset.productId);
            const newPrice = parseFloat(e.target.value);
            if (isNaN(newPrice) || newPrice < 0) return;
            const cartItem = cart.find(i => i.productId === productId);
            if (cartItem) {
                cartItem.customPrice = newPrice;
                updateCartUI();
                saveCart();
            }
        }

        function saveCart() {
            localStorage.setItem('pos_cart', JSON.stringify(cart));
        }

        function loadCart() {
            var saved = localStorage.getItem('pos_cart');
            if (saved) {
                try {
                    cart = JSON.parse(saved);
                    // ensure customPrice exists
                    cart.forEach(item => {
                        if (item.customPrice === undefined) item.customPrice = null;
                    });
                    var modified = false;
                    cart = cart.filter(item => {
                        var product = getProductById(item.productId);
                        if (!product) return false;
                        if (product.type === 'product' && item.quantity > product.stock) {
                            console.warn(
                                `Reducing quantity for ${product.name} from ${item.quantity} to ${product.stock}`
                            );
                            item.quantity = product.stock;
                            if (item.quantity === 0) return false;
                            modified = true;
                        }
                        return true;
                    });
                    if (modified) saveCart();
                    updateCartUI();
                } catch (e) {
                    console.error(e);
                }
            }
        }

        function updateCustomerBalance() {
            var select = document.getElementById('customerSelect');
            var selectedId = select.value;
            var balanceDiv = document.getElementById('customerBalanceDisplay');
            var balanceSpan = document.getElementById('customerBalanceAmount');
            if (selectedId && customersList[selectedId] > 0) {
                balanceSpan.innerText = CURRENCY + customersList[selectedId].toFixed(2);
                balanceDiv.classList.remove('hidden');
            } else {
                balanceDiv.classList.add('hidden');
            }
            updateAmountPaidHint();
        }

        function updateAmountPaidHint() {
            var amountInput = document.getElementById('amountPaidInput');
            var grandTotal = parseFloat(document.getElementById('grandTotal').innerText.replace(CURRENCY, ''));
            var customerId = document.getElementById('customerSelect').value;
            var hint = document.getElementById('amountPaidHint');
            if (!customerId) {
                hint.innerText = 'Walk-in customers must pay full amount.';
                amountInput.value = grandTotal.toFixed(2);
                amountInput.readOnly = true;
            } else {
                amountInput.readOnly = false;
                var balance = customersList[customerId] || 0;
                if (balance > 0) hint.innerText =
                    `Customer owes ${CURRENCY}${balance.toFixed(2)}. You can pay more to reduce debt.`;
                else hint.innerText = 'You can pay less (remaining becomes debt) or full amount.';
                var currentVal = parseFloat(amountInput.value);
                if (isNaN(currentVal) || currentVal === 0) amountInput.value = grandTotal.toFixed(2);
            }
        }

        document.getElementById('customerSelect').addEventListener('change', updateCustomerBalance);
        document.getElementById('amountPaidInput').addEventListener('input', function() {
            var grandTotal = parseFloat(document.getElementById('grandTotal').innerText.replace(CURRENCY, ''));
            var val = parseFloat(this.value);
            if (isNaN(val)) this.value = grandTotal.toFixed(2);
            else if (val < 0) this.value = '0';
        });

        // Product grid rendering
        var activeCategory = 'all';
        var searchTerm = '';

        function renderProducts(filter, category) {
            filter = filter || '';
            category = category || 'all';
            var list = products.filter(p => {
                var matchSearch = !filter || p.name.toLowerCase().includes(filter.toLowerCase()) || (p.barcode && p
                    .barcode.includes(filter));
                var matchCategory = category === 'all' || p.category === category;
                return matchSearch && matchCategory;
            });
            var grid = document.getElementById('productGrid');
            if (list.length === 0) {
                grid.innerHTML = '<div class="col-span-full text-center text-slate-600 py-16">No products found</div>';
                return;
            }
            grid.innerHTML = list.map(p => {
                var badge = '';
                if (p.type === 'service') {
                    badge =
                        '<span class="absolute top-1 right-1 bg-purple-500/80 text-white text-[10px] px-1 py-0.5 rounded">Service</span>';
                } else if (p.stock <= 0) {
                    badge =
                        '<div class="absolute inset-0 bg-black/60 flex items-center justify-center"><span class="text-red-400 text-xs font-bold bg-red-500/20 px-2 py-1 rounded">Out of Stock</span></div>';
                } else if (p.stock <= 5) {
                    badge =
                        `<div class="absolute bottom-1 right-1 bg-amber-500/90 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Low: ${p.stock}</div>`;
                }
                var disabled = p.stock <= 0 ? ' opacity-50 cursor-not-allowed' : '';
                return `<div class="card p-3 flex flex-col cursor-pointer hover:border-brand-600/50 transition-colors border border-transparent min-w-0" onclick="addToCart(${p.id})">
            <div class="aspect-square overflow-hidden rounded-lg mb-3 bg-slate-800 relative">
                <img class="w-full h-full object-cover" src="${imgSrc(p)}" alt="${escHtml(p.name)}" onerror="imgError(this)">
                ${badge}
            </div>
            <h3 class="font-bold text-white text-sm line-clamp-2 flex-1">${escHtml(p.name)}</h3>
            <p class="text-xs text-slate-500 font-mono mt-0.5">${p.barcode || '—'}</p>
            <div class="flex items-center justify-between mt-3">
                <span class="text-lg font-bold text-brand-400">${CURRENCY}${p.price.toFixed(2)}</span>
                <button onclick="event.stopPropagation();addToCart(${p.id})" class="btn-primary p-2 rounded-lg${disabled}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </div>
        </div>`;
            }).join('');
        }

        // Category filters
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => {
                    var active = b === btn;
                    b.className =
                        'filter-btn flex-shrink-0 px-5 py-2 rounded-full font-medium text-sm transition-colors ' +
                        (active ? 'bg-brand-600 text-white font-semibold' :
                            'bg-surface-card text-slate-300 hover:bg-surface-card/80');
                });
                activeCategory = btn.innerText === 'All' ? 'all' : btn.innerText;
                renderProducts(searchTerm, activeCategory);
            });
        });

        document.getElementById('searchInput').addEventListener('input', e => {
            searchTerm = e.target.value;
            renderProducts(searchTerm, activeCategory);
        });
        document.getElementById('discountInput').addEventListener('input', updateCartUI);

        function setPaymentMethod(method) {
            paymentMethod = method;
            ['cash', 'mobile_money', 'card'].forEach(m => {
                var el = document.getElementById('pm-' + m);
                if (el) {
                    el.className = 'pay-method py-2 rounded-lg text-xs font-medium border transition-all ' + (m ===
                        method ? 'bg-brand-600 text-white border-brand-600' :
                        'bg-slate-800 text-slate-400 border-slate-700');
                }
            });
        }

        // Checkout
        document.getElementById('collectPaymentBtn').addEventListener('click', async function() {
            if (cart.length === 0) {
                alert('Cart is empty. Add items first.');
                return;
            }
            var btn = this;
            btn.disabled = true;
            btn.innerHTML = 'Processing…';

            var validItems = [];
            var missingIds = [];
            for (var i = 0; i < cart.length; i++) {
                var p = getProductById(cart[i].productId);
                if (!p) {
                    missingIds.push(cart[i].productId);
                } else {
                    var itemPrice = (p.allow_price_override && cart[i].customPrice) ? cart[i].customPrice : p
                        .price;
                    validItems.push({
                        id: cart[i].productId,
                        qty: cart[i].quantity,
                        price: itemPrice,
                        discount: 0
                    });
                }
            }
            if (missingIds.length > 0) {
                alert(`Missing products: ${missingIds.join(', ')}. Refresh page.`);
                btn.disabled = false;
                btn.innerHTML = 'COLLECT PAYMENT';
                return;
            }

            var t = calculateTotals();
            var customerId = document.getElementById('customerSelect').value || null;
            var amountPaid = parseFloat(document.getElementById('amountPaidInput').value || 0);
            var grandTotal = t.grandTotal;

            if (customerId === null && amountPaid < grandTotal - 0.01) {
                alert('Walk-in customers must pay the full amount.');
                btn.disabled = false;
                btn.innerHTML = 'COLLECT PAYMENT';
                return;
            }
            if (isNaN(amountPaid)) amountPaid = 0;

            var payload = {
                items: validItems,
                payments: [{
                    method: paymentMethod,
                    amount: amountPaid
                }],
                discount: t.discount,
                tax: 0,
                customer_id: customerId,
            };

            try {
                var res = await fetch('/pos/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify(payload)
                });
                var data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Server error');
                lastSaleId = data.sale_id;
                document.getElementById('completedRef').innerText = data.reference;
                var changeEl = document.getElementById('changeDisplay');
                if (data.change > 0) {
                    changeEl.classList.remove('hidden');
                    document.getElementById('changeAmount').innerText = CURRENCY + parseFloat(data.change)
                        .toFixed(2);
                } else changeEl.classList.add('hidden');
                document.getElementById('cartModal').classList.add('hidden');
                document.getElementById('saleCompleteModal').classList.remove('hidden');
                cart = [];
                localStorage.removeItem('pos_cart');
                updateCartUI();
                playBeep();
                if (customerId && data.new_balance !== undefined) {
                    customersList[customerId] = data.new_balance;
                    updateCustomerBalance();
                }
            } catch (e) {
                console.error(e);
                alert('Checkout failed: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'COLLECT PAYMENT';
            }
        });

        document.getElementById('printReceiptBtn').addEventListener('click', () => {
            if (lastSaleId) window.open('/pos/receipt/' + lastSaleId, '_blank');
        });

        function newSale() {
            document.getElementById('saleCompleteModal').classList.add('hidden');
            document.getElementById('customerSelect').value = '';
            document.getElementById('discountInput').value = 0;
            document.getElementById('amountPaidInput').value = '0';
            setPaymentMethod('cash');
            document.getElementById('searchInput').focus();
            updateCustomerBalance();
        }
        document.getElementById('receiptButton').addEventListener('click', () => {
            if (lastSaleId) window.open('/pos/receipt/' + lastSaleId, '_blank');
            else alert('Complete the sale first.');
        });

        // Add customer
        document.getElementById('addCustomerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            var data = {};
            new FormData(e.target).forEach((v, k) => data[k] = v);
            try {
                var res = await fetch('/customers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                if (!res.ok) {
                    // Handle validation errors (422 Unprocessable Entity)
                    const errorData = await res.json();
                    if (errorData.errors) {
                        // Show the first validation error message
                        const firstError = Object.values(errorData.errors).flat()[0];
                        alert(firstError);
                    } else if (errorData.message) {
                        alert(errorData.message);
                    } else {
                        alert('Failed to save customer.');
                    }
                    return;
                }

                const customer = await res.json();
                if (customer.id) {
                    var sel = document.getElementById('customerSelect');
                    var opt = new Option(customer.name, customer.id);
                    opt.dataset.balance = '0';
                    sel.appendChild(opt);
                    customersList[customer.id] = 0;
                    sel.value = customer.id;
                    updateCustomerBalance();
                    e.target.reset();
                    document.getElementById('customerModal').classList.add('hidden');
                    document.getElementById('cartModal').classList.remove('hidden');
                } else {
                    alert('Failed to save customer.');
                }
            } catch (e) {
                alert('Network error: ' + e.message);
            }
        });

        // Barcode scanner (USB)
        var _buf = '',
            _timer = null;
        document.addEventListener('keydown', function(e) {
            if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;
            if (e.key === 'Enter' && _buf.length > 2) {
                var found = products.find(p => p.barcode === _buf);
                if (found) addToCart(found.id);
                else {
                    document.getElementById('searchInput').value = _buf;
                    renderProducts(_buf, activeCategory);
                }
                _buf = '';
            } else if (e.key.length === 1) {
                _buf += e.key;
                clearTimeout(_timer);
                _timer = setTimeout(() => {
                    _buf = '';
                }, 200);
            }
        });

        // Camera scanner
        var html5QrCode = null;
        async function startScanner() {
            try {
                await navigator.mediaDevices.getUserMedia({
                    video: true
                });
            } catch (err) {
                alert(err.name === 'NotAllowedError' ? 'Camera permission denied.' : 'Camera not available');
                return;
            }
            document.getElementById('scannerOverlay').classList.remove('hidden');
            document.getElementById('manualBarcode').value = '';
            if (!html5QrCode) html5QrCode = new Html5Qrcode('qr-reader');
            html5QrCode.start({
                facingMode: 'environment'
            }, {
                fps: 15,
                qrbox: {
                    width: 280,
                    height: 280
                }
            }, function(decodedText) {
                var p = products.find(x => x.barcode === decodedText);
                if (p) addToCart(p.id);
                else alert('Barcode ' + decodedText + ' not found');
                stopScanner();
            }, function() {}).catch(err => {
                alert('Could not start scanner: ' + err.message);
                stopScanner();
            });
        }

        function stopScanner() {
            if (html5QrCode && html5QrCode.isScanning) html5QrCode.stop().then(() => document.getElementById(
                'scannerOverlay').classList.add('hidden')).catch(() => document.getElementById('scannerOverlay')
                .classList.add('hidden'));
            else document.getElementById('scannerOverlay').classList.add('hidden');
        }

        function handleManualBarcode() {
            var barcode = document.getElementById('manualBarcode').value.trim();
            if (!barcode) return;
            var p = products.find(x => x.barcode === barcode);
            if (p) {
                addToCart(p.id);
                stopScanner();
            } else alert('Product with barcode "' + barcode + '" not found.');
            document.getElementById('manualBarcode').value = '';
        }
        document.getElementById('scanButton').addEventListener('click', startScanner);
        document.getElementById('closeScanner').addEventListener('click', stopScanner);
        document.getElementById('submitBarcode').addEventListener('click', handleManualBarcode);
        document.getElementById('manualBarcode').addEventListener('keypress', e => {
            if (e.key === 'Enter') handleManualBarcode();
        });

        // Cart modal open/close
        document.getElementById('cartButton').addEventListener('click', () => {
            updateCustomerBalance();
            document.getElementById('cartModal').classList.remove('hidden');
        });
        document.getElementById('closeCartModal').addEventListener('click', () => document.getElementById('cartModal')
            .classList.add('hidden'));
        document.getElementById('cartModal').addEventListener('click', e => {
            if (e.target === document.getElementById('cartModal')) document.getElementById('cartModal').classList
                .add('hidden');
        });

        function playBeep() {
            try {
                var ctx = new AudioContext();
                var osc = ctx.createOscillator();
                var g = ctx.createGain();
                osc.connect(g);
                g.connect(ctx.destination);
                osc.frequency.value = 1200;
                g.gain.setValueAtTime(0.1, ctx.currentTime);
                g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
                osc.start();
                osc.stop(ctx.currentTime + 0.08);
            } catch (e) {}
        }

        function escHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Init
        renderProducts();
        loadCart();
        document.getElementById('searchInput').focus();
        updateCustomerBalance();
    </script>
@endpush
