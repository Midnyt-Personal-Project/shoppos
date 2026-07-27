@extends('layouts.app')

@section('title', 'POS | OmniPOS')

@push('styles')
    <script defer src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        /* ── Scrollbar ── */
        .no-scrollbar::-webkit-scrollbar { display: none; }

        /* ── Sidebar (fixed cart) ── */
        .cart-sidebar {
            width: 480px;
            background: #0f172a;
            border-left: 1px solid #1e293b;
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            z-index: 30;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            padding-right: 480px;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Product Table ── */
        .product-table-wrap {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 1rem;
            overflow: hidden;
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .product-table-wrap .table-scroll {
            flex: 1;
            overflow-y: auto;
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        .product-table thead {
            position: sticky;
            top: 0;
            background: #1a2332;
            z-index: 5;
        }
        .product-table th {
            padding: 0.6rem 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.65rem;
            text-transform: uppercase;
            border-bottom: 1px solid #2d3a52;
        }
        .product-table td {
            padding: 0.5rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #1e293b;
        }
        .product-table tr:hover {
            background: #1a2332;
        }
        .product-table .product-img {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            object-fit: cover;
        }
        .product-table .add-btn {
            background: #0ea5e9;
            border: none;
            color: #fff;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .product-table .add-btn:hover { background: #0284c7; transform: scale(1.05); }
        .product-table .add-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .product-table .badge-service {
            background: #7c3aed;
            color: #fff;
            font-size: 0.55rem;
            padding: 0.1rem 0.4rem;
            border-radius: 12px;
            margin-left: 0.3rem;
        }

        /* ── Cart Table (inside sidebar) ── */
        .cart-table-wrap {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem 0.75rem;
            min-height: 0;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
            table-layout: fixed;
        }
        .cart-table thead {
            position: sticky;
            top: 0;
            background: #0f172a;
            z-index: 3;
        }
        .cart-table th {
            padding: 0.3rem 0.4rem;
            text-align: left;
            color: #94a3b8;
            font-size: 0.6rem;
            text-transform: uppercase;
            border-bottom: 1px solid #1e293b;
        }
        .cart-table td {
            padding: 0.3rem 0.4rem;
            vertical-align: middle;
            border-bottom: 1px solid #1e293b;
            white-space: nowrap;
        }
        .cart-table .col-cart-img {
            width: 30px;
        }
        .cart-table .col-cart-img img {
            width: 26px;
            height: 26px;
            border-radius: 5px;
            object-fit: cover;
            background: #1e293b;
        }
        .cart-table .col-cart-name {
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .cart-table .col-cart-price {
            width: 60px;
        }
        .cart-table .col-cart-price .editable-price {
            width: 50px;
        }
        .cart-table .col-cart-qty {
            width: 90px;
            white-space: nowrap;
        }
        .cart-table .col-cart-qty input {
            width: 34px;
            text-align: center;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 5px;
            color: #fff;
            font-size: 0.7rem;
            padding: 0.1rem 0;
        }
        .cart-table .col-cart-qty input:focus {
            outline: none;
            border-color: #0ea5e9;
        }
        .cart-table .col-cart-qty .qty-btn {
            background: #1e293b;
            border: none;
            color: #94a3b8;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 700;
            transition: 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .cart-table .col-cart-qty .qty-btn:hover {
            background: #2d3a52;
            color: #fff;
        }
        .cart-table .col-cart-total {
            width: 70px;
            font-weight: 600;
            color: #22d3ee;
            font-size: 0.75rem;
            text-align: right;
        }
        .cart-table .col-cart-remove {
            width: 30px;
            text-align: center;
        }
        .cart-table .col-cart-remove button {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 0.7rem;
            padding: 0.1rem 0.3rem;
            border-radius: 4px;
            transition: 0.15s;
        }
        .cart-table .col-cart-remove button:hover {
            background: #2d1a1a;
        }
        .cart-table .empty-cart td {
            padding: 2rem 0.5rem;
            text-align: center;
            color: #64748b;
            font-style: italic;
            font-size: 0.85rem;
        }
        .cart-table .editable-price {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 4px;
            color: #fff;
            font-size: 0.7rem;
            padding: 0.05rem 0.2rem;
            width: 54px;
            text-align: right;
        }
        .cart-table .editable-price:focus {
            outline: none;
            border-color: #0ea5e9;
        }

        /* ── Sidebar header & footer ── */
        .cart-sidebar-header {
            padding: 0.5rem 1rem;
            border-bottom: 1px solid #1e293b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .cart-sidebar-payment {
            padding: 0.5rem 1rem;
            border-top: 1px solid #1e293b;
            flex-shrink: 0;
            background: #0f172a;
        }

        /* ── Responsive ── */
        @media (max-width: 1200px) {
            .cart-sidebar { width: 400px; }
            .main-content { padding-right: 400px; }
        }
        @media (max-width: 1024px) {
            .cart-sidebar { width: 340px; }
            .main-content { padding-right: 340px; }
        }
        @media (max-width: 768px) {
            .main-content {
                height: auto;
                min-height: calc(100dvh - 64px);
                padding: 1rem;
                padding-bottom: 6rem;
                gap: 1rem;
            }
            .product-table-wrap { min-height: 320px; }
            .cart-sidebar {
                width: 100%;
                height: min(68dvh, 620px);
                bottom: 0;
                top: auto;
                border-left: none;
                border-top: 1px solid #1e293b;
                transform: translateY(100%);
                transition: transform 0.3s ease;
                z-index: 40;
            }
            .cart-sidebar.open { transform: translateY(0); }
            .main-content { padding-right: 0; }
            .cart-sidebar-payment { padding-bottom: max(0.5rem, env(safe-area-inset-bottom)); }
            .cart-toggle-mobile { bottom: max(1rem, env(safe-area-inset-bottom)); }
            .cart-toggle-mobile {
                display: flex !important;
            }
        }
        .cart-toggle-mobile {
            display: none;
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 45;
            width: 56px;
            height: 56px;
            background: #0ea5e9;
            color: #fff;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.5);
            cursor: pointer;
            font-size: 1.5rem;
        }

        /* Scanner overlay */
        #scannerOverlay {
            transition: opacity 0.2s ease;
            display: none;
        }
        #scannerOverlay.open { display: flex; }
        #scannerOverlay.hidden { display: none; }
        /* html5-qrcode injects the live <video> element into this container. */
        #qr-reader {
            min-height: 320px;
            background: #020617;
            border-radius: 1rem;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #qr-reader > div, #qr-reader__scan_region { width: 100% !important; }
        #qr-reader video {
            display: block !important;
            width: 100% !important;
            height: 340px !important;
            object-fit: cover !important;
            border-radius: 1rem;
        }
        #qr-reader img { display: none !important; }
        .scanner-reticle {
            pointer-events: none;
            position: absolute;
            top: 50%; left: 50%;
            width: min(62vw, 250px); height: min(62vw, 250px);
            transform: translate(-50%, -50%);
            border: 3px solid rgba(74, 222, 128, .95);
            border-radius: 1rem;
            box-shadow: 0 0 0 999px rgba(0, 0, 0, .18), 0 0 24px rgba(74, 222, 128, .45);
            z-index: 2;
        }
        .scanner-reticle::after {
            content: '';
            position: absolute; left: .5rem; right: .5rem; top: 50%;
            height: 2px; background: #4ade80;
            box-shadow: 0 0 10px #4ade80;
            animation: scanLine 1.8s ease-in-out infinite;
        }
        @keyframes scanLine { 0%,100% { transform: translateY(-95px); } 50% { transform: translateY(95px); } }
        @media (max-width: 480px) {
            #qr-reader { min-height: 270px; }
            #qr-reader video { height: 290px !important; }
            .scanner-reticle { width: 210px; height: 210px; }
        }
    </style>
@endpush

@section('content')
    <!-- Main Content -->
    <div class="main-content w-full min-w-0 space-y-6 p-6">

        <!-- Search & Scan -->
        <div class="flex gap-3 items-center flex-shrink-0">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input id="searchInput" class="input pl-10 py-3 w-full" placeholder="Search name or barcode..." type="text">
            </div>
            <button id="scanButton" type="button" title="Scan a barcode or QR code with your camera" class="btn-primary flex flex-col items-center justify-center w-14 h-14 rounded-xl flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4M12 12h4.01M20 12h4M4 12H2m-2 0h4M12 20h4M4 20h4" />
                </svg>
                <span class="text-[10px] font-bold uppercase mt-1">Scan</span>
            </button>
        </div>

        <!-- Category Filters -->
        <div class="overflow-hidden w-full max-w-2xl mx-auto flex-shrink-0">
            <div class="overflow-x-auto overflow-y-hidden no-scrollbar">
                <div class="flex gap-2 whitespace-nowrap px-2">
                    <button class="filter-btn flex-shrink-0 px-5 py-2 rounded-full bg-brand-600 text-white font-semibold text-sm" data-cat="all">All</button>
                    @if (isset($categories) && count($categories) > 0)
                        @foreach ($categories as $cat)
                            <button class="filter-btn flex-shrink-0 px-5 py-2 rounded-full bg-surface-card text-slate-300 font-medium text-sm hover:bg-surface-card/80 transition-colors" data-cat="{{ $cat }}">{{ $cat }}</button>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Product Table -->
        <div class="product-table-wrap">
            <div class="table-scroll">
                <table class="product-table" id="productTable">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="width:80px;">Barcode</th>
                            <th>Product</th>
                            <th style="width:80px;">Category</th>
                            <th style="width:70px;">Price</th>
                            <th style="width:60px;">Stock</th>
                            <th style="width:50px;">Add</th>
                        </tr>
                    </thead>
                    <tbody id="productBody">
                        <!-- rows injected by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== CART SIDEBAR ==================== -->
    <div id="cartSidebar" class="cart-sidebar">
        <!-- Header -->
        <div class="cart-sidebar-header">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-bold text-white">Cart</h2>
                <span class="text-xs text-brand-400 bg-brand-600/20 px-3 py-1 rounded-full" id="orderReference">#{{ rand(1000, 9999) }}</span>
            </div>
            <button id="closeSidebarMobile" class="lg:hidden p-2 rounded-lg hover:bg-surface-card transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Cart Table -->
        <div class="cart-table-wrap" id="cartTableWrap">
            <table class="cart-table" id="cartTable">
                <thead>
                    <tr>
                        <th class="col-cart-img">#</th>
                        <th class="col-cart-name">Product</th>
                        <th class="col-cart-price">Price</th>
                        <th class="col-cart-qty">Qty</th>
                        <th class="col-cart-total">Total</th>
                        <th class="col-cart-remove"></th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                    <tr class="empty-cart"><td colspan="6" class="text-center text-slate-400 py-8">Cart is empty</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Payment & Totals -->
        <div class="cart-sidebar-payment space-y-2">
            <!-- Totals -->
            <div class="space-y-1">
                <!-- Subtotal row with clear button -->
                <div class="flex justify-between text-sm items-center">
                    <span class="text-slate-400">Subtotal</span>
                    <div class="flex items-center gap-2">
                        <span id="subtotal" class="text-white font-semibold">{{ auth()->user()->shop->currency_symbol }}0.00</span>
                        <button id="clearCartBtn" class="text-red-400 hover:text-red-300 transition-colors disabled:opacity-40 disabled:cursor-not-allowed p-1" title="Clear Cart" disabled>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 bg-surface-card rounded-lg px-3 py-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <input id="discountInput" class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-white placeholder:text-slate-500" placeholder="Discount ({{ auth()->user()->shop->currency_symbol }})" type="number" min="0" step="0.01" value="0">
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">Tax</span>
                    <span id="taxAmount" class="text-white font-semibold">{{ auth()->user()->shop->currency_symbol }}0.00</span>
                </div>
                <div class="flex justify-between items-end pt-1 border-t border-surface-border">
                    <span class="text-slate-400 uppercase text-xs font-bold">Total</span>
                    <h2 id="grandTotal" class="text-2xl font-bold text-white">{{ auth()->user()->shop->currency_symbol }}0.00</h2>
                </div>
            </div>

            <!-- Customer -->
            <select id="customerSelect" class="input w-full text-sm">
                <option value="">— Walk-in Customer —</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" data-balance="{{ $customer->outstanding_balance }}" data-credit-limit="{{ $customer->credit_limit }}" data-email="{{ $customer->email }}">
                        {{ $customer->name }} .📞. {{ $customer->phone }}
                        
                    </option>
                @endforeach
            </select>
            <div id="customerBalanceDisplay" class="text-xs text-slate-400 hidden">
                Outstanding debt: <span id="customerBalanceAmount" class="text-amber-400 font-semibold"></span>
            </div>

            <!-- Amount Paid -->
            <div>
                <label class="text-slate-400 text-xs mb-0.5 block">Amount Paid</label>
                <div class="relative">
                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400">{{ auth()->user()->shop->currency_symbol }}</span>
                    <input type="number" id="amountPaidInput" step="0.01" min="0" class="input pl-7 w-full" value="0.00">
                </div>
                <p id="amountPaidHint" class="text-xs text-slate-500 mt-0.5"></p>
            </div>

            <!-- Payment Methods -->
            <div class="grid grid-cols-3 gap-1.5">
                <button onclick="setPaymentMethod('cash')" id="pm-cash" class="pay-method py-1.5 rounded-lg text-xs font-medium border transition-all bg-brand-600 text-white border-brand-600">Cash</button>
                <button onclick="setPaymentMethod('mobile_money')" id="pm-mobile_money" class="pay-method py-1.5 rounded-lg text-xs font-medium border transition-all bg-slate-800 text-slate-400 border-slate-700">MoMo</button>
                <button onclick="setPaymentMethod('card')" id="pm-card" class="pay-method py-1.5 rounded-lg text-xs font-medium border transition-all bg-slate-800 text-slate-400 border-slate-700">Card</button>
            </div>

            <!-- Reference -->
            <div id="paymentReferenceContainer" class="hidden">
                <label class="text-slate-400 text-xs mb-0.5 block">Reference Number</label>
                <input type="text" id="paymentReference" class="input w-full" placeholder="Transaction ID / reference">
            </div>

            <!-- Action Buttons -->
            <button id="collectPaymentBtn" class="btn-primary w-full py-2 justify-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                </svg>
                COLLECT PAYMENT
            </button>

            <!-- Three buttons: Receipt, Invoice, Customer -->
            <div class="flex gap-2">
                <button id="receiptButton" class="btn-secondary flex-1 justify-center gap-1 text-sm">🖨️ Receipt</button>
                <button id="invoiceButton" class="btn-secondary flex-1 justify-center gap-1 text-sm">📄 Invoice</button>
                <button onclick="document.getElementById('customerModal').classList.remove('hidden')" class="btn-secondary flex-1 justify-center gap-1 text-sm">👤 Customer</button>
            </div>
        </div>
    </div>

    <!-- Floating buttons -->
    <div class="cart-toggle-mobile" id="cartToggleMobile">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21h6" />
        </svg>
        <span id="cartBadgeMobile" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">0</span>
    </div>

    <!-- ==================== MODALS ==================== -->

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
            <div class="grid grid-cols-2 gap-2 mt-6">
                <button id="printReceiptBtn" class="btn-secondary justify-center py-2.5 text-sm">🖨️ Print</button>
                <button id="whatsappReceiptBtn" class="btn-secondary justify-center py-2.5 text-sm">📱 WhatsApp</button>
                <button id="emailReceiptBtn" class="btn-secondary justify-center py-2.5 text-sm">✉️ Email</button>
                <button onclick="newSale()" class="btn-primary justify-center py-2.5 text-sm">New Sale →</button>
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
                <div><label class="text-slate-400 text-xs mb-1 block">Address</label><input type="text" name="address" class="input"></div>
                <div class="flex gap-3 mt-4">
                    <button type="button" onclick="document.getElementById('customerModal').classList.add('hidden')" class="btn-secondary flex-1">Cancel</button>
                    <button type="submit" class="btn-primary flex-1">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scanner Overlay -->
    <div id="scannerOverlay" class="fixed inset-0 z-50 bg-black/90 hidden flex-col items-center justify-center p-4">
        <div class="relative w-full max-w-md mx-auto">
            <div id="qr-reader" style="width: 100%;">
                <span class="text-slate-400 text-sm">Starting camera preview…</span>
            </div>
            <div class="scanner-reticle" aria-hidden="true"></div>
            <p id="scannerStatus" class="text-slate-300 text-sm text-center mt-3">Opening camera…</p>
            <button id="closeScanner" class="absolute top-2 right-2 p-2 rounded-full bg-white/20 hover:bg-white/30 transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
        <p class="text-white text-sm mt-4">Position a barcode or QR code in front of the camera</p>
    </div>
@endsection

@push('scripts')
    <script>
        // ─── Config ──────────────────────────────────────────────────
        const CURRENCY = '{{ auth()->user()->shop->currency_symbol }}';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const products = @json($products);
        const TAX_RATES = @json($taxRates);

        // ─── Helpers ────────────────────────────────────────────────
        function getProductById(id) { return products.find(p => p.id === id); }
        function imgSrc(p) { return p.image ? '/storage/' + p.image : (p.type === 'service' ? '/service.png' : '/default.jpeg'); }
        function imgError(el) { el.onerror = null; el.src = '/default.jpg'; }
        function escHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
        function playBeep() { try { const ctx = new AudioContext(); const osc = ctx.createOscillator(); const g = ctx.createGain(); osc.connect(g); g.connect(ctx.destination); osc.frequency.value = 1200; g.gain.setValueAtTime(0.1, ctx.currentTime); g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08); osc.start(); osc.stop(ctx.currentTime + 0.08); } catch(_) {} }

        function calculateTax(subtotal, discount) {
            const taxable = Math.max(0, subtotal - discount);
            let total = 0;
            for (const t of TAX_RATES) total += taxable * (t.rate / 100);
            return total;
        }

        // ─── Cart state ──────────────────────────────────────────────
        let cart = [];
        let paymentMethod = 'cash';
        let lastSaleId = null;
        const customersList = {};
        document.querySelectorAll('#customerSelect option').forEach(opt => {
            if (opt.value) {
                customersList[opt.value] = {
                    balance: parseFloat(opt.dataset.balance || 0),
                    email: opt.dataset.email || null,
                    credit: parseFloat(opt.dataset.creditLimit) || null,
                    address: opt.dataset.address || null,
                };
            }
        });

        // ─── DOM refs ────────────────────────────────────────────────
        const productBody = document.getElementById('productBody');
        const cartBody = document.getElementById('cartBody');
        const searchInput = document.getElementById('searchInput');
        const discountInput = document.getElementById('discountInput');
        const customerSelect = document.getElementById('customerSelect');
        const amountPaidInput = document.getElementById('amountPaidInput');
        const payHint = document.getElementById('amountPaidHint');
        const balanceDisplay = document.getElementById('customerBalanceDisplay');
        const balanceAmount = document.getElementById('customerBalanceAmount');
        const refContainer = document.getElementById('paymentReferenceContainer');
        const paymentReference = document.getElementById('paymentReference');

        // ─── Render product table ──────────────────────────────────
        let activeCategory = 'all';
        let searchTerm = '';

        function renderProducts() {
            const term = searchTerm.toLowerCase().trim();
            let list = products.filter(p => {
                const matchSearch = !term || p.name.toLowerCase().includes(term) || (p.barcode && p.barcode.includes(term));
                const matchCat = activeCategory === 'all' || p.category === activeCategory;
                return matchSearch && matchCat;
            });

            if (list.length === 0) {
                productBody.innerHTML = `<tr><td colspan="7" class="text-center text-slate-600 py-8">No products found</td></tr>`;
                return;
            }

            let html = '';
            list.forEach(p => {
                const stock = p.type === 'service' ? '∞' : (p.stock ?? 0);
                const disabled = p.type !== 'service' && stock <= 0;
                const badge = p.type === 'service' ? '<span class="badge-service">Service</span>' : '';
                html += `
                    <tr>
                        <td><img class="product-img" src="${imgSrc(p)}" alt="" onerror="imgError(this)"></td>
                        <td class="text-slate-400 font-mono text-xs">${p.barcode || '—'}</td>
                        <td>${escHtml(p.name)} ${badge}</td>
                        <td class="text-slate-400 text-xs">${p.category || '—'}</td>
                        <td class="font-semibold text-cyan-400">${CURRENCY}${parseFloat(p.price).toFixed(2)}</td>
                        <td class="${stock <= 0 ? 'text-red-400' : (stock <= p.low_stock_threshold ? 'text-amber-400' : '')}">${stock}</td>
                        <td>
                            <button class="add-btn" ${disabled ? 'disabled' : ''} onclick="event.stopPropagation();window.addToCart(${p.id})">+</button>
                        </td>
                    </tr>
                `;
            });
            productBody.innerHTML = html;
        }

        // ─── Category filters ──────────────────────────────────────
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => {
                    const isActive = b === this;
                    b.className = 'filter-btn flex-shrink-0 px-5 py-2 rounded-full font-medium text-sm transition-colors ' +
                        (isActive ? 'bg-brand-600 text-white font-semibold' : 'bg-surface-card text-slate-300 hover:bg-surface-card/80');
                });
                activeCategory = this.dataset.cat;
                renderProducts();
            });
        });

        searchInput?.addEventListener('input', function() { searchTerm = this.value; renderProducts(); });
        discountInput?.addEventListener('input', updateCartUI);

        // ─── Add to cart ────────────────────────────────────────────
        function addToCart(productId) {
            const product = getProductById(productId);
            if (!product) return;
            if (product.type === 'service') {
                const existing = cart.find(i => i.productId === productId);
                if (existing) existing.quantity++;
                else cart.push({ productId, quantity: 1 });
                updateCartUI(); saveCart(); playBeep();
                return;
            }
            const stock = product.stock ?? 0;
            if (stock <= 0) { alert(`"${product.name}" is out of stock.`); return; }
            const existing = cart.find(i => i.productId === productId);
            const newQty = existing ? existing.quantity + 1 : 1;
            if (newQty > stock) { alert(`Only ${stock} left.`); return; }
            if (existing) existing.quantity++;
            else cart.push({ productId, quantity: 1 });
            updateCartUI(); saveCart(); playBeep();
        }

        // ─── Update cart UI ─────────────────────────────────────────
        function calculateTotals() {
            const discount = parseFloat(discountInput.value || 0);
            let subtotal = 0, itemCount = 0;
            cart.forEach(item => {
                const p = getProductById(item.productId);
                if (p) {
                    const price = (p.allow_price_override && item.customPrice) ? item.customPrice : p.price;
                    subtotal += price * item.quantity;
                    itemCount += item.quantity;
                }
            });
            const tax = calculateTax(subtotal, discount);
            const grand = Math.max(0, subtotal - discount + tax);
            return { subtotal, discount, tax, grand, itemCount };
        }

        function updateCartUI() {
            const t = calculateTotals();
            document.getElementById('subtotal').textContent = CURRENCY + t.subtotal.toFixed(2);
            document.getElementById('taxAmount').textContent = CURRENCY + t.tax.toFixed(2);
            document.getElementById('grandTotal').textContent = CURRENCY + t.grand.toFixed(2);
            document.getElementById('cartBadgeMobile').textContent = t.itemCount;

            // Clear button (now inline with subtotal)
            const clearBtn = document.getElementById('clearCartBtn');
            if (clearBtn) {
                clearBtn.disabled = cart.length === 0;
            }

            // Amount paid hint
            const customerId = customerSelect.value;
            if (!customerId) {
                amountPaidInput.value = t.grand.toFixed(2);
                amountPaidInput.readOnly = true;
                payHint.textContent = 'Walk-in: full amount required';
            } else {
                amountPaidInput.readOnly = false;
                if (parseFloat(amountPaidInput.value) === 0) amountPaidInput.value = t.grand.toFixed(2);
                const bal = customersList[customerId]?.balance || 0;
                payHint.textContent = bal > 0 ? `Owes ${CURRENCY}${bal.toFixed(2)}` : 'Can pay less (becomes debt)';
            }

            // Customer balance display
            updateCustomerBalance();

            // Render cart table
            if (cart.length === 0) {
                cartBody.innerHTML = `<tr class="empty-cart"><td colspan="6" class="text-center text-slate-400 py-8">Cart is empty</td></tr>`;
                return;
            }

            let html = '';
            cart.forEach(item => {
                const p = getProductById(item.productId);
                if (!p) return;
                const price = (p.allow_price_override && item.customPrice) ? item.customPrice : p.price;
                const lineTotal = price * item.quantity;
                const priceInput = p.allow_price_override ?
                    `<input class="editable-price" data-id="${p.id}" type="number" step="0.01" value="${price.toFixed(2)}" />` :
                    `${CURRENCY}${price.toFixed(2)}`;

                html += `
                    <tr>
                        <td class="col-cart-img"><img class="cart-img" src="${imgSrc(p)}" alt="" onerror="imgError(this)"></td>
                        <td class="col-cart-name">${escHtml(p.name)}</td>
                        <td class="col-cart-price">${priceInput}</td>
                        <td class="col-cart-qty">
                            <button class="qty-btn" onclick="window.updateQty(${p.id},-1)">−</button>
                            <input class="qty-input" data-id="${p.id}" type="number" min="1" value="${item.quantity}" />
                            <button class="qty-btn" onclick="window.updateQty(${p.id},1)">+</button>
                        </td>
                        <td class="col-cart-total">${CURRENCY}${lineTotal.toFixed(2)}</td>
                        <td class="col-cart-remove"><button onclick="window.removeFromCart(${p.id})">✕</button></td>
                    </tr>
                `;
            });
            cartBody.innerHTML = html;

            // Attach events
            document.querySelectorAll('.qty-input').forEach(inp => {
                inp.removeEventListener('change', handleQtyChange);
                inp.addEventListener('change', handleQtyChange);
            });
            document.querySelectorAll('.editable-price').forEach(inp => {
                inp.removeEventListener('input', handlePriceChange);
                inp.addEventListener('input', handlePriceChange);
            });
        }

        function handleQtyChange(e) {
            const id = parseInt(e.target.dataset.id);
            const val = parseInt(e.target.value);
            if (isNaN(val) || val < 1) { e.target.value = 1; setQty(id, 1); return; }
            setQty(id, val);
        }

        function handlePriceChange(e) {
            const id = parseInt(e.target.dataset.id);
            const val = parseFloat(e.target.value);
            if (isNaN(val) || val < 0) return;
            const item = cart.find(i => i.productId === id);
            if (item) { item.customPrice = val; updateCartUI(); saveCart(); }
        }

        function setQty(id, qty) {
            const p = getProductById(id);
            if (!p) return;
            const item = cart.find(i => i.productId === id);
            if (!item) return;
            if (p.type !== 'service' && qty > p.stock) {
                alert(`Max stock for "${p.name}" is ${p.stock}.`);
                const inp = document.querySelector(`.qty-input[data-id="${id}"]`);
                if (inp) inp.value = item.quantity;
                return;
            }
            if (qty <= 0) cart = cart.filter(i => i.productId !== id);
            else item.quantity = qty;
            updateCartUI();
            saveCart();
        }

        function updateQty(id, delta) {
            const item = cart.find(i => i.productId === id);
            if (!item) return;
            setQty(id, item.quantity + delta);
        }

        function removeFromCart(id) {
            cart = cart.filter(i => i.productId !== id);
            updateCartUI();
            saveCart();
        }

        function clearCart() {
            if (cart.length === 0) return;
            if (confirm('Clear entire cart?')) {
                cart = [];
                localStorage.removeItem('pos_cart');
                updateCartUI();
                playBeep();
                discountInput.value = 0;
                amountPaidInput.value = '0.00';
            }
        }
        document.getElementById('clearCartBtn')?.addEventListener('click', clearCart);

        // ─── Save / Load ─────────────────────────────────────────────
        function saveCart() { try { localStorage.setItem('pos_cart', JSON.stringify(cart)); } catch(_) {} }
        function loadCart() {
            try {
                const saved = localStorage.getItem('pos_cart');
                if (saved) {
                    cart = JSON.parse(saved);
                    cart.forEach(item => { if (item.customPrice === undefined) item.customPrice = null; });
                    let modified = false;
                    cart = cart.filter(item => {
                        const p = getProductById(item.productId);
                        if (!p) return false;
                        if (p.type !== 'service' && item.quantity > p.stock) {
                            item.quantity = p.stock;
                            modified = true;
                            if (item.quantity === 0) return false;
                        }
                        return true;
                    });
                    if (modified) saveCart();
                }
            } catch(_) {}
            updateCartUI();
        }

        // ─── Customer balance ──────────────────────────────────────
        function updateCustomerBalance() {
            const id = customerSelect.value;
            if (id && customersList[id] && customersList[id].balance > 0) {
                balanceDisplay.classList.remove('hidden');
                balanceAmount.textContent = CURRENCY + customersList[id].balance.toFixed(2);
            } else {
                balanceDisplay.classList.add('hidden');
            }
        }

        customerSelect?.addEventListener('change', function() {
            updateCustomerBalance();
            // Update amount paid hint
            const t = calculateTotals();
            if (!this.value) {
                amountPaidInput.value = t.grand.toFixed(2);
                amountPaidInput.readOnly = true;
                payHint.textContent = 'Walk-in: full amount required';
            } else {
                amountPaidInput.readOnly = false;
                if (parseFloat(amountPaidInput.value) === 0) amountPaidInput.value = t.grand.toFixed(2);
                const bal = customersList[this.value]?.balance || 0;
                payHint.textContent = bal > 0 ? `Owes ${CURRENCY}${bal.toFixed(2)}` : 'Can pay less (becomes debt)';
            }
        });

        // ─── Payment method ─────────────────────────────────────────
        function setPaymentMethod(method) {
            paymentMethod = method;
            document.querySelectorAll('.pay-method').forEach(btn => {
                const isActive = btn.id === `pm-${method}`;
                btn.className = 'pay-method py-1.5 rounded-lg text-xs font-medium border transition-all ' +
                    (isActive ? 'bg-brand-600 text-white border-brand-600' : 'bg-slate-800 text-slate-400 border-slate-700');
            });
            if (method === 'cash') {
                refContainer.classList.add('hidden');
                paymentReference.value = '';
            } else {
                refContainer.classList.remove('hidden');
            }
        }
        // Expose to global for inline onclick
        window.setPaymentMethod = setPaymentMethod;

        // ─── Collect Payment ────────────────────────────────────────
        document.getElementById('collectPaymentBtn')?.addEventListener('click', async function() {
            if (cart.length === 0) { alert('Cart is empty.'); return; }
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = 'Processing…';

            const validItems = [];
            let missing = false;
            for (const item of cart) {
                const p = getProductById(item.productId);
                if (!p) { missing = true; break; }
                const price = (p.allow_price_override && item.customPrice) ? item.customPrice : p.price;
                validItems.push({ id: p.id, qty: item.quantity, price, discount: 0 });
            }
            if (missing) { alert('Missing products. Refresh.'); btn.disabled = false; btn.innerHTML = 'COLLECT PAYMENT'; return; }

            const t = calculateTotals();
            const customerId = customerSelect.value || null;
            const amountPaid = parseFloat(amountPaidInput.value || 0);
            const ref = paymentReference.value.trim();

            if (!customerId && amountPaid < t.grand - 0.01) {
                alert('Walk-in must pay full amount.');
                btn.disabled = false; btn.innerHTML = 'COLLECT PAYMENT'; return;
            }
            if (isNaN(amountPaid)) { btn.disabled = false; btn.innerHTML = 'COLLECT PAYMENT'; return; }

            const payment = { method: paymentMethod, amount: amountPaid };
            if (paymentMethod !== 'cash' && ref) payment.reference = ref;

            const payload = {
                items: validItems,
                payments: [payment],
                discount: t.discount,
                tax: 0,
                customer_id: customerId,
            };

            try {
                const res = await fetch('/pos/checkout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Server error');

                lastSaleId = data.sale_id;
                document.getElementById('completedRef').textContent = data.reference || '#REF-' + lastSaleId;
                const changeEl = document.getElementById('changeDisplay');
                if (data.change > 0) {
                    changeEl.classList.remove('hidden');
                    document.getElementById('changeAmount').textContent = CURRENCY + parseFloat(data.change).toFixed(2);
                } else changeEl.classList.add('hidden');
                document.getElementById('saleCompleteModal').classList.remove('hidden');

                cart = [];
                localStorage.removeItem('pos_cart');
                updateCartUI();
                playBeep();

                if (customerId && data.new_balance !== undefined) {
                    if (customersList[customerId]) customersList[customerId].balance = data.new_balance;
                    updateCustomerBalance();
                }
            } catch (e) {
                alert('Checkout failed: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'COLLECT PAYMENT';
            }
        });

        // ─── Receipt / Invoice ──────────────────────────────────────
        document.getElementById('receiptButton')?.addEventListener('click', () => {
            if (lastSaleId) window.open('/pos/receipt/' + lastSaleId, '_blank');
            else alert('Complete the sale first.');
        });
        document.getElementById('printReceiptBtn')?.addEventListener('click', () => {
            if (lastSaleId) window.open('/pos/receipt/' + lastSaleId, '_blank');
        });

        document.getElementById('invoiceButton')?.addEventListener('click', generateInvoiceFromCart);
        async function generateInvoiceFromCart() {
            if (cart.length === 0) { alert('Cart is empty.'); return; }
            const btn = document.getElementById('invoiceButton');
            btn.disabled = true;
            btn.innerHTML = '⏳…';
            try {
                const items = cart.map(item => {
                    const p = getProductById(item.productId);
                    if (!p) return null;
                    const price = (p.allow_price_override && item.customPrice) ? item.customPrice : p.price;
                    return { id: p.id, name: p.name, qty: item.quantity, price, discount: 0 };
                }).filter(Boolean);
                if (!items.length) { alert('No valid items.'); return; }
                const t = calculateTotals();
                const payload = { items, customer_id: customerSelect.value || null, discount: t.discount, tax: t.tax, subtotal: t.subtotal, grand_total: t.grand };
                const res = await fetch('/sales/invoice-preview', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'text/html' },
                    body: JSON.stringify(payload)
                });
                if (!res.ok) throw new Error('Server error');
                const html = await res.text();
                const win = window.open('', '_blank');
                if (!win) { alert('Please allow popups.'); return; }
                win.document.open(); win.document.write(html); win.document.close();
                win.onload = () => { win.focus(); win.print(); };
            } catch (e) { alert('Invoice error: ' + e.message); } finally {
                btn.disabled = false;
                btn.innerHTML = '📄 Invoice';
            }
        }

        // ─── New Sale ───────────────────────────────────────────────
        function newSale() {
            document.getElementById('saleCompleteModal').classList.add('hidden');
            customerSelect.value = '';
            discountInput.value = 0;
            amountPaidInput.value = '0.00';
            setPaymentMethod('cash');
            paymentReference.value = '';
            updateCustomerBalance();
            searchInput.focus();
            updateCartUI();
        }
        // Expose to global for inline onclick
        window.newSale = newSale;

        // ─── Add Customer ────────────────────────────────────────────
        document.getElementById('addCustomerForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const data = {};
            new FormData(this).forEach((v,k) => data[k] = v);
            try {
                const res = await fetch('/customers', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify(data)
                });
                if (!res.ok) {
                    const err = await res.json();
                    alert(err.message || 'Failed');
                    return;
                }
                const c = await res.json();
                if (c.id) {
                    const opt = new Option(c.name, c.id);
                    opt.dataset.balance = '0';
                    opt.dataset.email = c.email || '';
                    customerSelect.appendChild(opt);
                    customersList[c.id] = { balance: 0, email: c.email || '' };
                    customerSelect.value = c.id;
                    updateCustomerBalance();
                    this.reset();
                    document.getElementById('customerModal').classList.add('hidden');
                }
            } catch (e) { alert('Network error: ' + e.message); }
        });

        // ─── Scanner ─────────────────────────────────────────────────
        let html5QrCode = null;
        let scannerStarting = false;
        const scannerStatus = document.getElementById('scannerStatus');

        async function startScanner() {
            if (scannerStarting || (html5QrCode && html5QrCode.isScanning)) return;
            if (typeof Html5Qrcode === 'undefined') {
                alert('The QR scanner library is still loading. Please check your internet connection and try again.');
                return;
            }
            if (!navigator.mediaDevices?.getUserMedia) {
                alert('Camera scanning is not supported by this browser. Use a USB scanner or enter the barcode manually.');
                return;
            }
            scannerStarting = true;
            document.getElementById('scannerOverlay').classList.add('open');
            document.getElementById('manualBarcode').value = '';
            scannerStatus.textContent = 'Finding the best camera…';
            const formats = typeof Html5QrcodeSupportedFormats === 'undefined' ? undefined : [
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.CODE_93,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
            ];
            const onScan = (decodedText) => {
                const product = products.find(p => String(p.barcode || '').trim() === String(decodedText).trim());
                if (product) { addToCart(product.id); stopScanner(); }
                else scannerStatus.textContent = `Code "${decodedText}" was not found. Keep scanning or enter it manually.`;
            };
            try {
                if (!html5QrCode) html5QrCode = new Html5Qrcode('qr-reader', formats ? { formatsToSupport: formats } : undefined);
                // Asking the QR Code JS library for devices first gives phones a real camera ID,
                // which is more reliable than a facingMode-only request and renders the preview consistently.
                const cameras = await Html5Qrcode.getCameras();
                const rearCamera = cameras.find(camera => /back|rear|environment|wide/i.test(camera.label));
                const camera = rearCamera?.id || cameras[0]?.id || { facingMode: { ideal: 'environment' } };
                scannerStatus.textContent = 'Camera ready — point it at the code.';
                await html5QrCode.start(
                    camera,
                    { fps: 12, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0, disableFlip: false },
                    onScan,
                    () => {}
                );
                scannerStatus.textContent = 'Live preview active. Put the barcode or QR code inside the green frame.';
            } catch (error) {
                scannerStatus.textContent = 'Camera preview could not start. Allow camera permission and use HTTPS (or localhost), or enter the barcode manually below.';
            } finally {
                scannerStarting = false;
            }
        }
        async function stopScanner() {
            try {
                if (html5QrCode && html5QrCode.isScanning) await html5QrCode.stop();
            } catch (_) { /* Always close the overlay even if a browser has already released the camera. */ }
            document.getElementById('scannerOverlay').classList.remove('open');
        }
        document.getElementById('scanButton')?.addEventListener('click', startScanner);
        document.getElementById('closeScanner')?.addEventListener('click', stopScanner);
        document.getElementById('submitBarcode')?.addEventListener('click', function() {
            const b = document.getElementById('manualBarcode').value.trim();
            if (!b) return;
            const p = products.find(x => x.barcode === b);
            if (p) { addToCart(p.id); stopScanner(); } else alert('Barcode "' + b + '" not found.');
            document.getElementById('manualBarcode').value = '';
        });
        document.getElementById('manualBarcode')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') document.getElementById('submitBarcode').click();
        });

        // ─── USB barcode scanner ────────────────────────────────────
        let barcodeBuf = '', barcodeTimer = null;
        document.addEventListener('keydown', function(e) {
            if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;
            if (e.key === 'Enter' && barcodeBuf.length > 2) {
                const found = products.find(p => p.barcode === barcodeBuf);
                if (found) addToCart(found.id);
                else { searchInput.value = barcodeBuf; searchTerm = barcodeBuf; renderProducts(); }
                barcodeBuf = '';
            } else if (e.key.length === 1) {
                barcodeBuf += e.key;
                clearTimeout(barcodeTimer);
                barcodeTimer = setTimeout(() => { barcodeBuf = ''; }, 200);
            }
        });

        // ─── WhatsApp / Email receipt ──────────────────────────────
        async function getSaleTextReceipt(saleId) {
            const res = await fetch(`/sales/${saleId}/receipt-data`);
            const sale = await res.json();
            const shop = sale.branch?.shop?.name || 'Shop';
            const branch = sale.branch?.name || '';
            const phone = sale.branch?.phone || '';
            const currency = sale.branch?.shop?.currency_symbol || '$';
            const date = new Date(sale.created_at).toLocaleString();
            const cashier = sale.user?.name || '';
            const customer = sale.customer?.name || 'Walk-in';
            const ref = sale.reference || '';
            let itemsText = '';
            if (sale.items) sale.items.forEach(item => {
                const total = (item.price * item.quantity) - (item.discount || 0);
                itemsText += `${item.product_name} x${item.quantity} = ${currency}${total.toFixed(2)}\n`;
            });
            let paymentsText = '';
            if (sale.payments) sale.payments.forEach(p => {
                paymentsText += `${p.method}: ${currency}${p.amount.toFixed(2)}\n`;
            });
            const subtotal = parseFloat(sale.subtotal) || 0;
            const discount = parseFloat(sale.discount) || 0;
            const tax = parseFloat(sale.tax_total) || 0;
            const total = parseFloat(sale.total) || 0;
            return `
        *${shop}*
        ${branch} ${phone}
        ────────────────────────────────
        Receipt: ${ref}
        Date: ${date}
        Cashier: ${cashier}
        Customer: ${customer}
        ────────────────────────────────
        ITEMS:
        ${itemsText}
        ────────────────────────────────
        Subtotal: ${currency}${subtotal.toFixed(2)}
        Discount: -${currency}${discount.toFixed(2)}
        Tax: ${currency}${tax.toFixed(2)}
        Total: ${currency}${total.toFixed(2)}
        ────────────────────────────────
        PAYMENTS:
        ${paymentsText}
        ────────────────────────────────
        Thank you! Come again.
        `;
        }

        document.getElementById('whatsappReceiptBtn')?.addEventListener('click', async () => {
            if (!lastSaleId) return;
            const text = await getSaleTextReceipt(lastSaleId);
            window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
        });

        document.getElementById('emailReceiptBtn')?.addEventListener('click', async () => {
            if (!lastSaleId) { alert('No sale.'); return; }
            const id = customerSelect.value;
            const email = id && customersList[id] ? customersList[id].email : null;
            if (!email) { alert('Customer has no email.'); return; }
            const btn = document.getElementById('emailReceiptBtn');
            btn.disabled = true;
            btn.innerHTML = '⏳…';
            try {
                const res = await fetch(`/sales/${lastSaleId}/email-receipt`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ email })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed');
                alert('Receipt sent to ' + email);
            } catch(e) { alert('Email error: ' + e.message); } finally {
                btn.disabled = false;
                btn.innerHTML = '✉️ Email';
            }
        });

        // ─── Mobile sidebar toggle ──────────────────────────────────
        document.getElementById('cartToggleMobile')?.addEventListener('click', function() {
            document.getElementById('cartSidebar').classList.toggle('open');
        });
        document.getElementById('closeSidebarMobile')?.addEventListener('click', function() {
            document.getElementById('cartSidebar').classList.remove('open');
        });

        // ─── Expose required functions to global scope ──────────────
        window.addToCart = addToCart;
        window.updateQty = updateQty;
        window.removeFromCart = removeFromCart;
        window.newSale = newSale;
        window.setPaymentMethod = setPaymentMethod;

        // ─── Init ────────────────────────────────────────────────────
        renderProducts();
        loadCart();
        searchInput?.focus();
        updateCustomerBalance();

        // Close modals on backdrop click
        document.querySelectorAll('.modal-overlay').forEach(el => {
            el.addEventListener('click', function(e) {
                if (e.target === this) this.classList.add('hidden');
            });
        });
        // For scanner overlay, close on backdrop click
        document.getElementById('scannerOverlay').addEventListener('click', function(e) {
            if (e.target === this) stopScanner();
        });
    </script>
@endpush