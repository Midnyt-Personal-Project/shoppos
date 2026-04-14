<?php

use App\Http\Controllers\{BranchController, CustomerController, DashboardController, ExpenseController, LicenseController, LoginController, PosController, ProductController, PurchaseOrderController, ReportController, SaleController, SettingController, SetupController, UserController};
use Illuminate\Support\Facades\{Route, Schedule};
use Native\Desktop\Facades\AutoUpdater;

Route::get('/check-for-updates', function () {
    try {
        AutoUpdater::checkForUpdates();
        return 'Update check initiated successfully. Check logs for details.';
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});
// ─── Auth ─────────────────────────────────────────────────────────────────────

 Route::get('/setup/check', [SetupController::class, 'check'])->name('setup.check');
 Route::post('/setup',      [SetupController::class, 'store'])->name('setup.store');


Route::middleware('guest')->group(function () {
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

Route::middleware(['auth', 'role', 'license'])->group(function () {

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
    });

    // ── Sales ─────────────────────────────────────────────────────────────────
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/',               [SaleController::class, 'index'])->name('index');
        Route::get('/{sale}',         [SaleController::class, 'show'])->name('show');
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
 
    // Receive items (managers and above)
    Route::middleware('role:owner,admin,manager')->group(function () {
        Route::post('/items/{item}/receive',       [PurchaseOrderController::class, 'receiveItem'])->name('receiveItem');
        Route::post('/{purchaseOrder}/receive-all',[PurchaseOrderController::class, 'receiveAll'])->name('receiveAll');
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
        Route::post('/{customer}/repay', [CustomerController::class, 'repayDebt'])->name('repay');
    });

    // ── Expenses ──────────────────────────────────────────────────────────────
    Route::middleware('role:owner,admin,manager')->group(function () {
        Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'destroy']);
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
    });

    // ── Settings (owner/admin only) ───────────────────────────────────────────
    Route::middleware('role:owner,admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/',                      [SettingController::class, 'index'])->name('index');
        Route::post('/general',              [SettingController::class, 'saveGeneral'])->name('general');
        Route::post('/notifications',        [SettingController::class, 'saveNotifications'])->name('notifications');
        Route::post('/email/{branch}',       [SettingController::class, 'saveBranchEmail'])->name('saveBranchEmail');
        Route::post('/email/{branch}/test',  [SettingController::class, 'testEmail'])->name('testEmail');
        Route::get('/email/{branch}/clear',  [SettingController::class, 'clearBranchEmail'])->name('clearBranchEmail');
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
    ->dailyAt('04:43')
    ->withoutOverlapping()
    ->runInBackground();
 
// Weekly debt reminder — every Monday at 9:00 AM
Schedule::command('omnipos:debt-reminder')
    ->weekly()
    ->mondays()
    ->at('04:41')
    ->withoutOverlapping()
    ->runInBackground();
});