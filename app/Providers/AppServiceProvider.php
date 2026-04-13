<?php

namespace App\Providers;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use App\Events\{SaleCompleted, StockLow};
use App\Listeners\{SendLowStockAlert, SendSaleNotification};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        // ── Event → Listener bindings ─────────────────────────────────────────
        Event::listen(SaleCompleted::class, SendSaleNotification::class);
        Event::listen(StockLow::class,      SendLowStockAlert::class);
    }
}
