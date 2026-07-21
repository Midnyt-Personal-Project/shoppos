<?php

use Illuminate\Support\Facades\{Route, Schedule};

use App\Http\Controllers\{BranchController, BranchSwitchController, CustomerController, DashboardController, ExpenseCategoryController, ExpenseController, LicenseController, LoginController, PeerController, PosController, ProductController, ProductImportController, PurchaseOrderController, ReportController, SaleController, SettingController, SetupController, TaxRateController, UpdateHistoryController, UserController};
use Native\Desktop\Facades\AutoUpdater;



// ───  Documentations (runs before auth) ─────────────────────────────────────────────
Route::get('/documentation', function () {
    return view('about.index');
})->name('documentation');

Route::get('/test-branch', function () {
    if (!auth()->check()) {
        return 'Not logged in';
    }
    return [
        'logged_in_user' => auth()->user()->name,
        'role' => auth()->user()->role,
        'session_branch_id' => session('current_branch_id'),
        'current_branch()' => current_branch()?->name,
        'user_own_branch_id' => auth()->user()->branch_id,
        'all_branches_in_shop' => auth()->user()->shop->branches->map(fn($b) => ['id' => $b->id, 'name' => $b->name]),
    ];
})->middleware('auth');


// ─── Auth ─────────────────────────────────────────────────────────────────────

Route::get('/setup/check', [SetupController::class, 'check'])->name('setup.check');
Route::post('/setup',      [SetupController::class, 'store'])->name('setup.store');

// 'throttle:1,1'

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/license',           [LicenseController::class, 'index'])->name('license.index');
    Route::post('/license/activate', [LicenseController::class, 'activate'])->name('license.activate');
    Route::post('/license/refresh',  [LicenseController::class, 'refresh'])->name('license.refresh');
});

// ─── Authenticated ─────────────────────────────────────────────────────────────
// Route::middleware(['auth', 'role', 'license'])->group(function () {
Route::middleware(['auth', 'role',])->group(function () {

    Route::get('/settings/peers', [App\Http\Controllers\PeerController::class, 'index'])->name('settings.peers.index');
    Route::post('/settings/peers', [App\Http\Controllers\PeerController::class, 'store'])->name('settings.peers.store');
    Route::put('/settings/peers/{peer}', [App\Http\Controllers\PeerController::class, 'update'])->name('settings.peers.update');
    Route::delete('/settings/peers/{peer}', [App\Http\Controllers\PeerController::class, 'destroy'])->name('settings.peers.destroy');


    Route::get('/updates', [UpdateHistoryController::class, 'index'])->name('updates.index');
    Route::post('/updates/check', [UpdateHistoryController::class, 'check'])->name('updates.check');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── POS ───────────────────────────────────────────────────────────────────
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/',               [PosController::class, 'index'])->name('index');
        Route::get('/search',         [PosController::class, 'searchProduct'])->name('search');
        Route::post('/checkout',      [PosController::class, 'checkout'])->name('checkout');
        Route::get('/receipt/{sale}', [PosController::class, 'receipt'])->name('receipt');
        Route::post('/refund/{sale}', [PosController::class, 'refund'])->name('refund')
            ->middleware('role:owner,admin,manager');
    });

    // ── Products ──────────────────────────────────────────────────────────────
    Route::middleware('role:owner,admin,manager')->group(function () {
        Route::resource('products', ProductController::class)->except(['show']);
        Route::post('products/{product}/restock',       [ProductController::class, 'restock'])->name('products.restock');
        Route::post('products/{product}/transfer',      [ProductController::class, 'transfer'])->name('products.transfer');
        Route::post('products/{product}/remove-branch', [ProductController::class, 'removeBranch'])->name('products.removeBranch');
        Route::get('products/import', [App\Http\Controllers\ProductImportController::class, 'showForm'])->name('products.import.form');
        Route::post('products/import', [ProductImportController::class, 'import'])->name('products.import.store');
        Route::get('/products/{product}/stock-logs', [ProductController::class, 'stockLogs'])->name('products.stock-logs');
        Route::post('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
        Route::get('/products/import/template', [ProductController::class, 'downloadTemplate'])->name('products.import.template');
    });

    //
    // ── Sales ─────────────────────────────────────────────────────────────────
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/',               [SaleController::class, 'index'])->name('index');
        Route::get('/{sale}',         [SaleController::class, 'show'])->name('show');
        // routes/web.php
        Route::post('/invoice-preview', [SaleController::class, 'invoicePreview'])->name('invoice-preview');
        Route::post('/{sale}/email-receipt', [SaleController::class, 'emailReceipt'])->name('email-receipt');
        Route::get('/{sale}/receipt-data', [SaleController::class, 'receiptData'])->name('receipt-data');
        Route::get('/{sale}/refund',  [SaleController::class, 'refundView'])->name('refund')
            ->middleware('role:owner,admin,manager');
    });

    // ── Purchase Orders ───────────────────────────────────────────────────────
    Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {

        // All authenticated users can view POs for their branch
        Route::get('/',                          [PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/create',                    [PurchaseOrderController::class, 'create'])->name('create');
        Route::post('/',                         [PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{purchaseOrder}',           [PurchaseOrderController::class, 'show'])->name('show');
        Route::get('/{purchaseOrder}/print',     [PurchaseOrderController::class, 'print'])->name('print');
        Route::get('/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('edit');
        Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update');

        // Receive items (managers and above)
        Route::middleware('role:owner,admin,manager')->group(function () {
            Route::post('/items/{item}/receive',       [PurchaseOrderController::class, 'receiveItem'])->name('receiveItem');
            Route::post('/{purchaseOrder}/receive-all', [PurchaseOrderController::class, 'receiveAll'])->name('receiveAll');
            Route::delete('/{purchaseOrder}',          [PurchaseOrderController::class, 'destroy'])->name('destroy');
        });



        // Approve / Reject (admins only)
        Route::middleware('role:owner,admin')->group(function () {
            Route::post('/{purchaseOrder}/approve',    [PurchaseOrderController::class, 'approve'])->name('approve');
            Route::post('/{purchaseOrder}/reject',     [PurchaseOrderController::class, 'reject'])->name('reject');
        });
    });

    // ── Customers ─────────────────────────────────────────────────────────────
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',                  [CustomerController::class, 'index'])->name('index');
        Route::post('/',                 [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}',        [CustomerController::class, 'show'])->name('show');
        Route::put('/{customer}',        [CustomerController::class, 'update'])->name('update');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::post('/{customer}/repay', [CustomerController::class, 'repayDebt'])->name('repay');
    });

    // ── Expenses ──────────────────────────────────────────────────────────────
    Route::middleware('role:owner,admin,manager,cashier')->group(function () {
        Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'destroy']);
        Route::resource('expense-categories', ExpenseCategoryController::class)->only(['index', 'store', 'destroy']);
        Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::get('/expenses/report', [ExpenseController::class, 'report'])->name('expenses.report');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::resource('expenses', ExpenseController::class)->except(['edit', 'update']); // already have index, store, destroy
        Route::get('/expenses/{expense}/download-receipt', [ExpenseController::class, 'downloadReceipt'])->name('expenses.download-receipt');
    });

    // ── Reports ───────────────────────────────────────────────────────────────
    Route::middleware('role:owner,admin,manager')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
    });

    // ── Users (admin only) ────────────────────────────────────────────────────
    Route::middleware('role:owner,admin')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    // ── Branches (admin only) ─────────────────────────────────────────────────
    Route::middleware('role:owner,admin')->group(function () {
        Route::resource('branches', BranchController::class)->only(['index', 'store', 'update']);
        Route::post('/branch/switch', [BranchSwitchController::class, 'switch'])->name('branch.switch');
    });

    // ── Settings (owner/admin only) ───────────────────────────────────────────
    Route::middleware('role:owner,admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/',                      [SettingController::class, 'index'])->name('index');
        Route::post('/general',              [SettingController::class, 'saveGeneral'])->name('general');
        Route::post('/notifications',        [SettingController::class, 'saveNotifications'])->name('notifications');
        Route::post('/email/{branch}',       [SettingController::class, 'saveBranchEmail'])->name('saveBranchEmail');
        Route::post('/email/{branch}/test',  [SettingController::class, 'testEmail'])->name('testEmail');
        Route::get('/email/{branch}/clear',  [SettingController::class, 'clearBranchEmail'])->name('clearBranchEmail');
        Route::get('/taxes',       [TaxRateController::class, 'index'])->name('taxes.index');
        Route::post('/taxes',       [TaxRateController::class, 'store'])->name('taxes.store');
        Route::put('/taxes/{tax}', [TaxRateController::class, 'update'])->name('taxes.update');
        Route::delete('/taxes/{tax}', [TaxRateController::class, 'destroy'])->name('taxes.destroy');
    });


    //
    /*
|--------------------------------------------------------------------------
| OmniPOS Scheduled Tasks
|--------------------------------------------------------------------------
|
| These run automatically as long as the Laravel scheduler is active.
|
| To activate on your server, add ONE cron entry:
|   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
|
| On Laravel Herd (local), run: php artisan schedule:work
|
*/

    // Daily sales summary — sent every day at 8:00 PM
    Schedule::command('omnipos:daily-summary')
        ->dailyAt('00:16')
        ->withoutOverlapping()
        ->runInBackground();

    // Weekly debt reminder — every Monday at 9:00 AM
    Schedule::command('omnipos:debt-reminder')
    ->mondays()
    ->at('06:40')
    ->withoutOverlapping()
    ->runInBackground();
});
