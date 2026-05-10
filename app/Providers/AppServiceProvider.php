<?php

namespace App\Providers;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

use App\Events\{SaleCompleted, StockLow};
use App\Listeners\{SendLowStockAlert, SendSaleNotification};
use App\Services\ExternalApiServer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         
        // ExternalApiServer::start();
    }
}