{{-- resources/views/about.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Documentation — OmniPOS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html, body {
            height: 100%;
            overflow: hidden; /* Prevents whole page scroll */
        }

        /* Right panel scroll only */
        .scrollable-content {
            overflow-y: auto;
            height: 100vh;
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        .scrollable-content::-webkit-scrollbar {
            width: 6px;
        }
        .scrollable-content::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 3px;
        }
        .scrollable-content::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 3px;
        }

        /* Anchor offset when scrolled into view */
        .anchor-link {
            scroll-margin-top: 80px;
        }

        /* Sidebar link styling */
        .sidebar-link {
            display: block;
            padding: 0.25rem 0;
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.15s;
        }
        .sidebar-link:hover {
            color: #4ade80;
        }
        .sidebar-link.active {
            color: #22c55e;
            font-weight: 500;
        }
    </style>
</head>

<body class="bg-slate-900 text-gray-200">

<div class="flex h-screen">

    {{-- LEFT SIDEBAR (STATIC / FIXED) --}}
    <aside class="w-80 border-r border-slate-700 flex-shrink-0 overflow-y-auto">
        <div class="p-6 space-y-6">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-700">
                <img src="{{ asset('icon.png') }}" alt="OmniPOS Logo" class="w-10 h-10 object-contain">
                <div>
                    <h2 class="text-white font-bold text-lg">OmniPOS</h2>
                    <p class="text-xs text-slate-400">Documentation</p>
                </div>
            </div>

            <ul class="space-y-2 text-sm">
                <li><a href="#overview" class="sidebar-link">➡️ Overview</a></li>
                <li><a href="#features" class="sidebar-link">➡️ Key Features</a></li>
                <li><a href="#installation" class="sidebar-link">➡️ Installation</a></li>
                <li><a href="#getting-started" class="sidebar-link">➡️ Getting Started</a></li>
                <li><a href="#user-roles" class="sidebar-link">➡️ User Roles & Permissions</a></li>
                <li><a href="#pos-guide" class="sidebar-link">➡️ Using the POS</a></li>
                <li><a href="#inventory" class="sidebar-link">➡️ Inventory & Purchase Orders</a></li>
                <li><a href="#reports" class="sidebar-link">➡️ Reports & Analytics</a></li>
                <li><a href="#settings" class="sidebar-link">➡️ Settings & Customisation</a></li>
                <li><a href="#license" class="sidebar-link">➡️ License & Activation</a></li>
                <li><a href="#shortcuts" class="sidebar-link">➡️ Keyboard Shortcuts</a></li>
                <li><a href="#troubleshooting" class="sidebar-link">➡️ Troubleshooting</a></li>
            </ul>
        </div>
    </aside>

    {{-- RIGHT CONTENT (SCROLLABLE) --}}
    <main class="flex-1 scrollable-content">
        <div class="max-w-4xl mx-auto px-8 py-10 space-y-12">

            <!-- Overview -->
            <section id="overview" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">Overview</h2>
                <p class="text-slate-300 leading-relaxed"><strong class="text-white">OmniPOS</strong> is a modern, native desktop Point of Sale application built with Laravel and NativePHP. Designed for single or multi‑branch retail businesses, it offers lightning‑fast checkout, real‑time inventory tracking, customer debt management, and powerful reporting – all wrapped in a beautiful dark interface that runs natively on Windows, macOS, and Linux.</p>
                <p class="text-slate-300 mt-3">The application follows a robust architecture: Laravel backend, Blade frontend (or Inertia/Vue), and NativePHP for native menus, auto‑updates, and system tray integration.</p>
            </section>

            <!-- Key Features -->
            <section id="features" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">Key Features</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><span class="font-bold text-emerald-400">💳 Fast POS</span> – Barcode scanning, instant search, cart management, and receipt printing.</div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><span class="font-bold text-emerald-400">📦 Stock Management</span> – Low stock alerts, branch transfers, restock via purchase orders.</div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><span class="font-bold text-emerald-400">🏢 Multi‑branch</span> – Separate inventory, users, and sales per location.</div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><span class="font-bold text-emerald-400">👥 Customer Debt</span> – Sell on credit, track repayments, automated reminders.</div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><span class="font-bold text-emerald-400">📊 Reports</span> – Sales summaries, profit margins, stock valuation, daily/weekly email reports.</div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><span class="font-bold text-emerald-400">🔐 Role‑based Access</span> – Owner, Admin, Manager, Cashier with granular permissions.</div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><span class="font-bold text-emerald-400">📧 Email Notifications</span> – Daily sales, debt reminders, low stock alerts.</div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><span class="font-bold text-emerald-400">🔄 Auto‑Updater</span> – One‑click updates with rollback support.</div>
                </div>
            </section>

            <!-- Installation -->
            <section id="installation" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">Installation</h2>
                <p class="text-slate-300">OmniPOS is distributed as a native installer for each platform. Download the latest version from the official website or build from source.</p>
                <div class="mt-4 space-y-3">
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><strong class="text-white">Windows:</strong> Run <code class="bg-slate-900 px-1.5 py-0.5 rounded text-sm">OmniPOS-Setup.exe</code> and follow the wizard.</div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><strong class="text-white">macOS:</strong> Open <code class="bg-slate-900 px-1.5 py-0.5 rounded text-sm">OmniPOS.dmg</code> and drag to Applications.</div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4"><strong class="text-white">Linux:</strong> Use the AppImage or <code class="bg-slate-900 px-1.5 py-0.5 rounded text-sm">.deb</code> package.</div>
                </div>
                <div class="mt-4 bg-slate-800/80 rounded-xl p-4 border border-slate-700">
                    <p class="font-mono text-sm text-slate-300"># Build from source<br>
                    git clone https://github.com/your-repo/omnipos.git<br>
                    cd omnipos<br>
                    composer install<br>
                    npm install<br>
                    cp .env.example .env<br>
                    php artisan key:generate<br>
                    php artisan migrate --seed<br>
                    php artisan native:build</p>
                </div>
            </section>

            <!-- Getting Started -->
            <section id="getting-started" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">Getting Started</h2>
                <p class="text-slate-300">On first launch, OmniPOS will run a setup wizard:</p>
                <ol class="list-decimal list-inside text-slate-300 space-y-1 mt-2">
                    <li>Database connection (SQLite recommended for small shops, MySQL for larger).</li>
                    <li>Create your first shop / business details.</li>
                    <li>Create the owner (admin) account.</li>
                    <li>Activate your license key (purchased from the official website).</li>
                </ol>
                <p class="text-slate-300 mt-3">After setup, log in with your admin credentials. The dashboard shows today’s sales, low stock items, and recent activity.</p>
            </section>

            <!-- User Roles -->
            <section id="user-roles" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">User Roles & Permissions</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-slate-800/50 rounded-xl border border-slate-700">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="px-4 py-2 text-left text-sm font-semibold text-white">Role</th>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-white">Permissions</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-300 text-sm">
                            <tr class="border-b border-slate-700"><td class="px-4 py-2 font-medium text-white">Owner</td><td class="px-4 py-2">Full control, license management, user roles, branches, all settings.</td></tr>
                            <tr class="border-b border-slate-700"><td class="px-4 py-2 font-medium text-white">Admin</td><td class="px-4 py-2">Everything except license and owner role assignment.</td></tr>
                            <tr class="border-b border-slate-700"><td class="px-4 py-2 font-medium text-white">Manager</td><td class="px-4 py-2">Create purchase orders, view reports, manage expenses, restock products.</td></tr>
                            <tr><td class="px-4 py-2 font-medium text-white">Cashier</td><td class="px-4 py-2">Only POS access (sales, customer lookup, refunds with approval).</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Using the POS -->
            <section id="pos-guide" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">Using the POS</h2>
                <p class="text-slate-300">Access the Point of Sale from the menu <strong class="text-white">File → New Sale</strong> or press <kbd class="bg-slate-800 px-2 py-0.5 rounded text-sm">Ctrl+N</kbd>.</p>
                <ul class="list-disc list-inside text-slate-300 space-y-1 mt-2">
                    <li><strong>Search products</strong> – Type name, SKU, or scan barcode (auto‑focus).</li>
                    <li><strong>Cart management</strong> – Adjust quantities, apply discounts, remove items.</li>
                    <li><strong>Customer selection</strong> – Attach sale to existing customer or create new on the fly.</li>
                    <li><strong>Payment</strong> – Cash, card, or credit (adds to customer debt).</li>
                    <li><strong>Receipt</strong> – Auto‑printed or emailed if configured.</li>
                    <li><strong>Refunds</strong> – From Sales History → View sale → Refund (manager+ role).</li>
                </ul>
                <div class="mt-4 bg-emerald-600/10 border border-emerald-600/20 rounded-xl p-3 text-sm text-emerald-300">
                    💡 <strong>Tip:</strong> Press <kbd class="bg-black/30 px-1 rounded">F2</kbd> to focus the product search box instantly.
                </div>
            </section>

            <!-- Inventory & Purchase Orders -->
            <section id="inventory" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">Inventory & Purchase Orders</h2>
                <p class="text-slate-300"><strong class="text-white">Products</strong> can be added/edited from the Products section (managers+). Each product includes name, SKU, barcode, price, cost, current stock, low stock threshold, and branch assignment.</p>
                <p class="text-slate-300 mt-3"><strong class="text-white">Purchase Orders</strong> restock inventory:</p>
                <ol class="list-decimal list-inside text-slate-300 space-y-1">
                    <li>Create PO – select supplier, add items and quantities.</li>
                    <li>Admin/Manager approves the PO.</li>
                    <li>Receive items (partial receives allowed) → stock increases automatically.</li>
                </ol>
                <p class="text-slate-300 mt-3">You can also <strong>transfer stock</strong> between branches directly from the product page.</p>
            </section>

            <!-- Reports -->
            <section id="reports" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">Reports & Analytics</h2>
                <p class="text-slate-300">Access all reports from the menu <strong class="text-white">File → Reports</strong>. Export to PDF or Excel.</p>
                <ul class="list-disc list-inside text-slate-300 space-y-1 mt-2">
                    <li><strong>Sales Report</strong> – Filter by date, branch, cashier. Shows revenue, profit, tax, payment methods.</li>
                    <li><strong>Stock Report</strong> – Current inventory value, low stock items, stock movement.</li>
                    <li><strong>Daily/Weekly Summaries</strong> – Auto‑emailed to branch managers (configurable).</li>
                    <li><strong>Customer Debt Report</strong> – Lists all customers with outstanding balances.</li>
                </ul>
            </section>

            <!-- Settings -->
            <section id="settings" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">Settings & Customisation</h2>
                <p class="text-slate-300">Go to <strong class="text-white">File → Settings</strong> (<kbd class="bg-slate-800 px-2 py-0.5 rounded">Ctrl+,</kbd>).</p>
                <ul class="list-disc list-inside text-slate-300 space-y-1 mt-2">
                    <li><strong>General</strong> – Company name, tax rate, receipt footer, currency.</li>
                    <li><strong>Notifications</strong> – Low stock alerts, debt reminders, daily summary emails.</li>
                    <li><strong>Email Configuration</strong> – SMTP settings per branch.</li>
                    <li><strong>Printer</strong> – Select default receipt printer (thermal or standard).</li>
                </ul>
            </section>

            <!-- License Activation -->
            <section id="license" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">License & Activation</h2>
                <p class="text-slate-300">OmniPOS requires a valid license key. Navigate to <strong class="text-white">System → License / Activation Code</strong> to enter your key. The license is tied to your company/branch count.</p>
                <div class="mt-3 bg-yellow-500/10 border-l-4 border-yellow-500 p-3 rounded text-yellow-300 text-sm">
                    ⚠️ Offline grace period: 7 days. After that, the POS remains functional but updates and support are disabled.
                </div>
            </section>

            <!-- Keyboard Shortcuts -->
            <section id="shortcuts" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">Keyboard Shortcuts</h2>
                <div class="grid md:grid-cols-2 gap-2 text-sm text-slate-300">
                    <div><kbd class="bg-slate-800 px-2 py-0.5 rounded">Ctrl+N</kbd> – New Sale</div>
                    <div><kbd class="bg-slate-800 px-2 py-0.5 rounded">Ctrl+H</kbd> – Sales History</div>
                    <div><kbd class="bg-slate-800 px-2 py-0.5 rounded">Ctrl+R</kbd> – Sales Report</div>
                    <div><kbd class="bg-slate-800 px-2 py-0.5 rounded">Ctrl+,</kbd> – Settings</div>
                    <div><kbd class="bg-slate-800 px-2 py-0.5 rounded">Ctrl+Q</kbd> – Quit</div>
                    <div><kbd class="bg-slate-800 px-2 py-0.5 rounded">Ctrl+Shift+D</kbd> – Documentation</div>
                    <div><kbd class="bg-slate-800 px-2 py-0.5 rounded">F2</kbd> – Focus product search (POS)</div>
                    <div><kbd class="bg-slate-800 px-2 py-0.5 rounded">F5</kbd> – Refresh view</div>
                </div>
            </section>

            <!-- Troubleshooting -->
            <section id="troubleshooting" class="anchor-link">
                <h2 class="text-3xl font-bold text-white border-b border-slate-700 pb-2 mb-4">Troubleshooting</h2>
                <div class="space-y-3 text-slate-300">
                    <div><strong class="text-white">❌ App won’t start</strong> – Check antivirus; ensure Windows 10+, macOS 11+, Linux with glibc 2.28+.</div>
                    <div><strong class="text-white">🔑 Invalid license</strong> – Verify internet connection and key. Contact support.</div>
                    <div><strong class="text-white">🖨️ Receipt not printing</strong> – Go to Settings → Printer, select correct printer, test print.</div>
                    <div><strong class="text-white">📧 Emails not sending</strong> – Verify SMTP settings; use “Test Email” in Settings.</div>
                    <div><strong class="text-white">🐛 Other issues</strong> – Check logs: <code class="bg-slate-800 px-1.5 py-0.5 rounded text-sm">~/AppData/Roaming/OmniPOS/logs/</code> (Windows) or <code class="bg-slate-800 px-1.5 py-0.5 rounded text-sm">~/Library/Logs/OmniPOS/</code> (macOS).</div>
                </div>
                <p class="mt-4 text-slate-300">Need more help? Email <a href="mailto:support@omnipos.com" class="text-emerald-400 hover:underline">support@omnipos.com</a> or visit our <a href="#" class="text-emerald-400 hover:underline">community forum</a>.</p>
            </section>

            <hr class="border-slate-700 my-8">
            <footer class="text-center text-slate-500 text-sm py-4">
                © {{ date('Y') }} OmniPOS. All rights reserved. | Version 1.0.0
            </footer>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const scrollContainer = document.querySelector('.scrollable-content');
        const navLinks = document.querySelectorAll('.sidebar-link');

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement && scrollContainer) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });
</script>

</body>
</html>