@extends('layouts.app')

@section('title', 'POS | OmniPOS')

@push('styles')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        #qr-reader { background: #000; border-radius: 1rem; overflow: hidden; }
        #qr-reader video { border-radius: 1rem; }
        #scannerOverlay { transition: opacity 0.2s ease; }
        #scannerOverlay.hidden { display: none; }
        @media (max-width: 640px) {
            main.p-6 { padding-left: 1rem; padding-right: 1rem; }
            .grid { gap: 0.75rem; }
            .card { padding: 0.75rem; }
        }
    </style>
@endpush

@section('content')
<div class="space-y-6">

    <!-- Search & Scan -->
    <div class="flex gap-3">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input id="searchInput" class="input pl-10 py-3" placeholder="Search name or barcode..." type="text">
        </div>
        <button id="scanButton" class="btn-primary flex flex-col items-center justify-center w-14 h-14 rounded-xl">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4M12 12h4.01M20 12h4M4 12H2m-2 0h4M12 20h4M4 20h4"/>
            </svg>
            <span class="text-[10px] font-bold uppercase mt-1">Scan</span>
        </button>
    </div>

    <!-- Category Filters — rendered from $categories passed by controller -->
    <div class="flex gap-2 overflow-x-auto no-scrollbar py-1">
        <button class="filter-btn flex-shrink-0 px-5 py-2 rounded-full bg-brand-600 text-white font-semibold text-sm" data-category="all">All</button>
        @foreach($categories as $cat)
        <button class="filter-btn flex-shrink-0 px-5 py-2 rounded-full bg-surface-card text-slate-300 font-medium text-sm hover:bg-surface-card/80 transition-colors"
                data-category="{{ $cat }}">{{ $cat }}</button>
        @endforeach
    </div>

    <!-- Product Grid -->
    <div id="productGrid" class="grid grid-cols-2 gap-4 pb-8"></div>

    <!-- Floating Cart Button -->
    <div class="fixed bottom-24 right-6 z-40">
        <button id="cartButton" class="w-14 h-14 bg-brand-600 text-white rounded-full shadow-lg flex items-center justify-center relative hover:bg-brand-500 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21h6"/>
            </svg>
            <div id="cartBadge" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">0</div>
        </button>
    </div>

    <!-- Cart Modal -->
    <div id="cartModal" class="fixed inset-0 z-50 hidden bg-black/50 flex items-end md:items-center justify-center p-4">
        <div class="card w-full max-w-md md:max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-surface-border">
                <div class="flex items-center gap-2">
                    <button id="closeCartModal" class="p-2 rounded-lg hover:bg-surface-card transition-colors">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <h2 class="text-lg font-bold text-white">Current Order</h2>
                </div>
                <span class="text-xs text-brand-400 bg-brand-600/20 px-3 py-1 rounded-full">#{{ rand(1000,9999) }}</span>
            </div>

            <div id="cartItemsList" class="flex-1 overflow-y-auto p-4 space-y-3">
                <div class="text-center text-slate-400 py-8">Cart is empty</div>
            </div>

            <div class="border-t border-surface-border p-4 space-y-3">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Subtotal</span>
                        <span id="subtotal" class="text-white font-semibold">{{ auth()->user()->shop->currency_symbol }}0.00</span>
                    </div>
                    <div class="flex items-center gap-2 bg-surface-card rounded-lg px-3 py-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <input id="discountInput"
                               class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-white placeholder:text-slate-500"
                               placeholder="Discount ({{ auth()->user()->shop->currency_symbol }})"
                               type="number" min="0" step="0.01" value="0">
                    </div>
                </div>

                <div class="flex justify-between items-end pt-2">
                    <div>
                        <span class="text-xs text-slate-400 uppercase font-bold">Grand Total</span>
                        <h2 id="grandTotal" class="text-3xl font-bold text-white">{{ auth()->user()->shop->currency_symbol }}0.00</h2>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-400">Stock Verified</span>
                        <p id="itemCount" class="text-sm text-slate-400">0 Items</p>
                    </div>
                </div>

                <!-- Customer selector -->
                <select id="customerSelect" class="input w-full text-sm">
                    <option value="">— Walk-in Customer —</option>
                    @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">
                        {{ $customer->name }}
                        @if($customer->outstanding_balance > 0)
                            (Owes {{ auth()->user()->shop->currency_symbol }}{{ number_format($customer->outstanding_balance, 2) }})
                        @endif
                    </option>
                    @endforeach
                </select>

                <!-- Payment method -->
                <div class="grid grid-cols-3 gap-2">
                    <button onclick="setPaymentMethod('cash')" id="pm-cash"
                            class="pay-method py-2 rounded-lg text-xs font-medium border transition-all bg-brand-600 text-white border-brand-600">Cash</button>
                    <button onclick="setPaymentMethod('mobile_money')" id="pm-mobile_money"
                            class="pay-method py-2 rounded-lg text-xs font-medium border transition-all bg-slate-800 text-slate-400 border-slate-700">MoMo</button>
                    <button onclick="setPaymentMethod('card')" id="pm-card"
                            class="pay-method py-2 rounded-lg text-xs font-medium border transition-all bg-slate-800 text-slate-400 border-slate-700">Card</button>
                </div>

                <button id="collectPaymentBtn" class="btn-primary w-full py-3 justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    COLLECT PAYMENT
                </button>

                <div class="flex gap-3">
                    <button id="receiptButton" class="btn-secondary flex-1 justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"/>
                        </svg>
                        Receipt
                    </button>
                    <button onclick="document.getElementById('cartModal').classList.add('hidden');document.getElementById('customerModal').classList.remove('hidden')"
                            class="btn-secondary flex-1 justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Customer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sale Complete Modal -->
    <div id="saleCompleteModal" class="fixed inset-0 z-50 hidden bg-black/60 flex items-center justify-center p-4">
        <div class="card w-96 p-8 text-center">
            <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
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
                <div><label class="text-slate-400 text-xs mb-1 block">Name *</label><input type="text" name="name" required class="input"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Phone</label><input type="tel" name="phone" class="input"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Email</label><input type="email" name="email" class="input"></div>
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
    <div id="scannerOverlay" class="fixed inset-0 z-50 bg-black/90 hidden flex flex-col items-center justify-center p-4">
        <div class="relative w-full max-w-md mx-auto">
            <div id="qr-reader" style="width: 100%;"></div>
            <button id="closeScanner" class="absolute top-2 right-2 p-2 rounded-full bg-white/20 hover:bg-white/30 transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="mt-6 w-full max-w-md">
            <div class="bg-surface-card rounded-lg p-4">
                <p class="text-slate-300 text-sm mb-2 text-center">Having trouble? Enter barcode manually:</p>
                <div class="flex gap-2">
                    <input type="text" id="manualBarcode" class="input flex-1" placeholder="Enter barcode number" autocomplete="off">
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
const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
const products = @json($products);

// ── Default placeholder (encoded SVG — no backtick template literals) ─────
var DEFAULT_IMAGE = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%231e293b'/%3E%3Cpath d='M70 80 L100 60 L130 80 L130 120 L100 140 L70 120 Z' fill='none' stroke='%23475569' stroke-width='3'/%3E%3Cpath d='M70 80 L100 100 L130 80' stroke='%23475569' stroke-width='2' fill='none'/%3E%3Cpath d='M100 100 L100 140' stroke='%23475569' stroke-width='2'/%3E%3C/svg%3E";

function imgSrc(product) {
    return product.image ? '/storage/' + product.image : DEFAULT_IMAGE;
}
function imgError(el) {
    el.onerror = null;
    el.src = DEFAULT_IMAGE;
}

// ── Cart ───────────────────────────────────────────────────────────────────
var cart          = [];
var paymentMethod = 'cash';
var lastSaleId    = null;

function getProductById(id) {
    return products.find(function(p) { return p.id === id; });
}

function addToCart(productId) {
    var product = getProductById(productId);
    if (!product) return;
    if (product.stock <= 0) { alert('"' + product.name + '" is out of stock.'); return; }
    var existing = cart.find(function(i) { return i.productId === productId; });
    if (existing) { existing.quantity++; }
    else { cart.push({ productId: productId, quantity: 1 }); }
    updateCartUI();
    saveCart();
    playBeep();
}

function updateQuantity(productId, delta) {
    var item = cart.find(function(i) { return i.productId === productId; });
    if (item) {
        item.quantity += delta;
        if (item.quantity <= 0) {
            cart = cart.filter(function(i) { return i.productId !== productId; });
        }
    }
    updateCartUI();
    saveCart();
}

function removeItem(productId) {
    cart = cart.filter(function(i) { return i.productId !== productId; });
    updateCartUI();
    saveCart();
}

function calculateTotals() {
    var discount  = parseFloat(document.getElementById('discountInput').value || 0);
    var subtotal  = 0;
    var itemCount = 0;
    cart.forEach(function(item) {
        var p = getProductById(item.productId);
        if (p) { subtotal += p.price * item.quantity; itemCount += item.quantity; }
    });
    return {
        subtotal:   subtotal,
        discount:   discount,
        grandTotal: Math.max(0, subtotal - discount),
        itemCount:  itemCount
    };
}

function updateCartUI() {
    var t = calculateTotals();
    document.getElementById('cartBadge').innerText  = t.itemCount;
    document.getElementById('itemCount').innerText  = t.itemCount + ' Items';
    document.getElementById('subtotal').innerText   = CURRENCY + t.subtotal.toFixed(2);
    document.getElementById('grandTotal').innerText = CURRENCY + t.grandTotal.toFixed(2);

    if (cart.length === 0) {
        document.getElementById('cartItemsList').innerHTML =
            '<div class="text-center text-slate-400 py-8">Cart is empty</div>';
        return;
    }

    var html = '';
    cart.forEach(function(item) {
        var p = getProductById(item.productId);
        if (!p) return;
        var lineTotal = p.price * item.quantity;
        html += '<div class="bg-surface-card p-4 rounded-xl">'
            + '<div class="flex gap-3">'
            + '<img class="w-16 h-16 rounded-lg object-cover bg-surface-DEFAULT flex-shrink-0"'
            + ' src="' + imgSrc(p) + '" alt="' + escHtml(p.name) + '" onerror="imgError(this)">'
            + '<div class="flex-1 min-w-0">'
            + '<h3 class="font-semibold text-white text-sm">' + escHtml(p.name) + '</h3>'
            + '<p class="text-xs text-slate-500 font-mono">' + (p.barcode || '—') + '</p>'
            + '<div class="flex items-center justify-between mt-2">'
            + '<div class="flex items-center gap-2">'
            + '<button onclick="updateQuantity(' + p.id + ',-1)" class="w-7 h-7 rounded-md bg-slate-700 hover:bg-slate-600 text-white flex items-center justify-center">−</button>'
            + '<span class="text-white font-bold w-6 text-center">' + item.quantity + '</span>'
            + '<button onclick="updateQuantity(' + p.id + ',1)"  class="w-7 h-7 rounded-md bg-slate-700 hover:bg-slate-600 text-white flex items-center justify-center">+</button>'
            + '</div>'
            + '<div class="text-right">'
            + '<p class="text-brand-400 text-xs">' + CURRENCY + p.price.toFixed(2) + ' ea</p>'
            + '<p class="text-white font-bold text-sm">' + CURRENCY + lineTotal.toFixed(2) + '</p>'
            + '</div></div></div></div>'
            + '<div class="flex justify-end mt-2">'
            + '<button onclick="removeItem(' + p.id + ')" class="text-red-400 hover:text-red-300 text-xs">✕ Remove</button>'
            + '</div></div>';
    });
    document.getElementById('cartItemsList').innerHTML = html;
}

function saveCart() { localStorage.setItem('pos_cart', JSON.stringify(cart)); }
function loadCart() {
    var saved = localStorage.getItem('pos_cart');
    if (saved) { try { cart = JSON.parse(saved); updateCartUI(); } catch(e) {} }
}

// ── Product grid ───────────────────────────────────────────────────────────
var activeCategory = 'all';
var searchTerm     = '';

function renderProducts(filter, category) {
    filter   = filter   || '';
    category = category || 'all';

    var list = products.filter(function(p) {
        var matchSearch   = !filter
            || p.name.toLowerCase().indexOf(filter.toLowerCase()) !== -1
            || (p.barcode && p.barcode.indexOf(filter) !== -1);
        var matchCategory = category === 'all' || p.category === category;
        return matchSearch && matchCategory;
    });

    var grid = document.getElementById('productGrid');
    if (list.length === 0) {
        grid.innerHTML = '<div class="col-span-2 text-center text-slate-600 py-16">No products found</div>';
        return;
    }

    grid.innerHTML = list.map(function(p) {
        var badge = '';
        if (p.stock <= 0) {
            badge = '<div class="absolute inset-0 bg-black/60 flex items-center justify-center">'
                  + '<span class="text-red-400 text-xs font-bold bg-red-500/20 px-2 py-1 rounded">Out of Stock</span></div>';
        } else if (p.stock <= 5) {
            badge = '<div class="absolute bottom-1 right-1 bg-amber-500/90 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Low: ' + p.stock + '</div>';
        }
        var disabled = p.stock <= 0 ? ' opacity-50 cursor-not-allowed' : '';
        return '<div class="card p-3 flex flex-col cursor-pointer hover:border-brand-600/50 transition-colors border border-transparent" onclick="addToCart(' + p.id + ')">'
            + '<div class="aspect-square overflow-hidden rounded-lg mb-3 bg-slate-800 relative">'
            + '<img class="w-full h-full object-cover" src="' + imgSrc(p) + '" alt="' + escHtml(p.name) + '" onerror="imgError(this)">'
            + badge + '</div>'
            + '<h3 class="font-bold text-white text-sm line-clamp-2 flex-1">' + escHtml(p.name) + '</h3>'
            + '<p class="text-xs text-slate-500 font-mono mt-0.5">' + (p.barcode || '—') + '</p>'
            + '<div class="flex items-center justify-between mt-3">'
            + '<span class="text-lg font-bold text-brand-400">' + CURRENCY + p.price.toFixed(2) + '</span>'
            + '<button onclick="event.stopPropagation();addToCart(' + p.id + ')" class="btn-primary p-2 rounded-lg' + disabled + '">'
            + '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
            + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg></button>'
            + '</div></div>';
    }).join('');
}

// ── Category filter buttons ────────────────────────────────────────────────
document.querySelectorAll('.filter-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(function(b) {
            var active = b === btn;
            b.className = 'filter-btn flex-shrink-0 px-5 py-2 rounded-full font-medium text-sm transition-colors '
                + (active ? 'bg-brand-600 text-white font-semibold' : 'bg-surface-card text-slate-300 hover:bg-surface-card/80');
        });
        activeCategory = btn.dataset.category;
        renderProducts(searchTerm, activeCategory);
    });
});

document.getElementById('searchInput').addEventListener('input', function(e) {
    searchTerm = e.target.value;
    renderProducts(searchTerm, activeCategory);
});

document.getElementById('discountInput').addEventListener('input', updateCartUI);

// ── Payment method ─────────────────────────────────────────────────────────
function setPaymentMethod(method) {
    paymentMethod = method;
    ['cash', 'mobile_money', 'card'].forEach(function(m) {
        var el = document.getElementById('pm-' + m);
        if (!el) return;
        el.className = 'pay-method py-2 rounded-lg text-xs font-medium border transition-all '
            + (m === method ? 'bg-brand-600 text-white border-brand-600'
                            : 'bg-slate-800 text-slate-400 border-slate-700');
    });
}

// ── Checkout ───────────────────────────────────────────────────────────────
document.getElementById('collectPaymentBtn').addEventListener('click', async function() {
    if (cart.length === 0) { alert('Cart is empty. Add items first.'); return; }

    var btn = document.getElementById('collectPaymentBtn');
    btn.disabled  = true;
    btn.innerHTML = 'Processing…';

    var t = calculateTotals();
    var payload = {
        items: cart.map(function(i) {
            var p = getProductById(i.productId);
            return { id: i.productId, qty: i.quantity, price: p.price, discount: 0 };
        }),
        payments:    [{ method: paymentMethod, amount: t.grandTotal }],
        discount:    t.discount,
        tax:         0,
        customer_id: document.getElementById('customerSelect').value || null,
    };

    try {
        var res  = await fetch('/pos/checkout', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body:    JSON.stringify(payload),
        });
        var data = await res.json();

        if (data.success) {
            lastSaleId = data.sale_id;
            document.getElementById('completedRef').innerText = data.reference;
            var changeEl = document.getElementById('changeDisplay');
            if (data.change > 0) {
                changeEl.classList.remove('hidden');
                document.getElementById('changeAmount').innerText = CURRENCY + parseFloat(data.change).toFixed(2);
            } else {
                changeEl.classList.add('hidden');
            }
            document.getElementById('cartModal').classList.add('hidden');
            document.getElementById('saleCompleteModal').classList.remove('hidden');
            cart = [];
            localStorage.removeItem('pos_cart');
            updateCartUI();
            playBeep();
        } else {
            alert('Error: ' + (data.message || 'Checkout failed'));
        }
    } catch(e) {
        alert('Network error. Please try again.');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = 'COLLECT PAYMENT';
    }
});

document.getElementById('printReceiptBtn').addEventListener('click', function() {
    if (lastSaleId) window.open('/pos/receipt/' + lastSaleId, '_blank');
});

function newSale() {
    document.getElementById('saleCompleteModal').classList.add('hidden');
    document.getElementById('customerSelect').value = '';
    document.getElementById('discountInput').value  = 0;
    setPaymentMethod('cash');
    document.getElementById('searchInput').focus();
}

document.getElementById('receiptButton').addEventListener('click', function() {
    if (lastSaleId) window.open('/pos/receipt/' + lastSaleId, '_blank');
    else alert('Complete the sale first to print a receipt.');
});

// ── Add customer ───────────────────────────────────────────────────────────
document.getElementById('addCustomerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    var data = {};
    new FormData(e.target).forEach(function(v, k) { data[k] = v; });
    try {
        var res      = await fetch('/customers', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body:    JSON.stringify(data),
        });
        var customer = await res.json();
        if (customer.id) {
            var sel = document.getElementById('customerSelect');
            sel.appendChild(new Option(customer.name, customer.id, true, true));
            e.target.reset();
            document.getElementById('customerModal').classList.add('hidden');
            document.getElementById('cartModal').classList.remove('hidden');
        } else { alert('Failed to save customer.'); }
    } catch(e) { alert('Error saving customer.'); }
});

// ── USB barcode scanner (keyboard wedge) ──────────────────────────────────
var _buf = '', _timer = null;
document.addEventListener('keydown', function(e) {
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;
    if (e.key === 'Enter' && _buf.length > 2) {
        var found = products.find(function(p) { return p.barcode === _buf; });
        if (found) addToCart(found.id);
        else { document.getElementById('searchInput').value = _buf; renderProducts(_buf, activeCategory); }
        _buf = '';
    } else if (e.key.length === 1) {
        _buf += e.key;
        clearTimeout(_timer);
        _timer = setTimeout(function() { _buf = ''; }, 200);
    }
});

// ── Camera scanner ─────────────────────────────────────────────────────────
var html5QrCode = null;

async function startScanner() {
    try {
        var stream = await navigator.mediaDevices.getUserMedia({ video: true });
        stream.getTracks().forEach(function(t) { t.stop(); });
    } catch(err) {
        alert(err.name === 'NotAllowedError' ? 'Camera permission denied.' : 'Camera not available: ' + err.message);
        return;
    }
    document.getElementById('scannerOverlay').classList.remove('hidden');
    document.getElementById('manualBarcode').value = '';
    if (!html5QrCode) html5QrCode = new Html5Qrcode('qr-reader');
    html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 15, qrbox: { width: 280, height: 280 }, experimentalFeatures: { useBarCodeDetectorIfSupported: true } },
        function(decodedText) {
            var p = products.find(function(x) { return x.barcode === decodedText; });
            if (p) addToCart(p.id);
            else alert('Barcode ' + decodedText + ' not found');
            stopScanner();
        },
        function() {}
    ).catch(function(err) { alert('Could not start scanner: ' + (err.message || err)); stopScanner(); });
}

function stopScanner() {
    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop()
            .then(function() { document.getElementById('scannerOverlay').classList.add('hidden'); })
            .catch(function() { document.getElementById('scannerOverlay').classList.add('hidden'); });
    } else {
        document.getElementById('scannerOverlay').classList.add('hidden');
    }
}

function handleManualBarcode() {
    var barcode = document.getElementById('manualBarcode').value.trim();
    if (!barcode) return;
    var p = products.find(function(x) { return x.barcode === barcode; });
    if (p) { addToCart(p.id); stopScanner(); }
    else alert('Product with barcode "' + barcode + '" not found.');
    document.getElementById('manualBarcode').value = '';
}

document.getElementById('scanButton').addEventListener('click', startScanner);
document.getElementById('closeScanner').addEventListener('click', stopScanner);
document.getElementById('submitBarcode').addEventListener('click', handleManualBarcode);
document.getElementById('manualBarcode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') handleManualBarcode();
});

// ── Cart modal open/close ──────────────────────────────────────────────────
document.getElementById('cartButton').addEventListener('click', function() {
    document.getElementById('cartModal').classList.remove('hidden');
});
document.getElementById('closeCartModal').addEventListener('click', function() {
    document.getElementById('cartModal').classList.add('hidden');
});
document.getElementById('cartModal').addEventListener('click', function(e) {
    if (e.target === document.getElementById('cartModal')) {
        document.getElementById('cartModal').classList.add('hidden');
    }
});

// ── Beep ───────────────────────────────────────────────────────────────────
function playBeep() {
    try {
        var ctx = new AudioContext();
        var osc = ctx.createOscillator();
        var g   = ctx.createGain();
        osc.connect(g); g.connect(ctx.destination);
        osc.frequency.value = 1200;
        g.gain.setValueAtTime(0.1, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
        osc.start(); osc.stop(ctx.currentTime + 0.08);
    } catch(e) {}
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// ── Init ───────────────────────────────────────────────────────────────────
renderProducts();
loadCart();
document.getElementById('searchInput').focus();
</script>
@endpush