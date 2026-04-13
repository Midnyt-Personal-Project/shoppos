<?php

namespace App\Listeners;

use App\Events\StockLow;
use App\Models\ShopSetting;
use App\Services\MailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendLowStockAlert implements ShouldQueue
{
    public function handle(StockLow $event): void
    {
        $branch = $event->branch->load('shop');
        $shopId = $branch->shop_id;

        Log::info('SendLowStockAlert: Stock low alert triggered', [
            'branch' => $branch->name,
            'items_count' => count($event->items),
        ]);

        // Check toggle
        $notifyEnabled = ShopSetting::get($shopId, 'notify_low_stock', true);
        Log::debug('SendLowStockAlert: Checking notify_low_stock toggle', [
            'shop_id' => $shopId,
            'notify_low_stock' => $notifyEnabled,
        ]);
        
        if (! $notifyEnabled) {
            Log::info('SendLowStockAlert: Skipped - notify_low_stock is disabled');
            return;
        }

        Log::info('SendLowStockAlert: Sending alert mail', [
            'branch' => $branch->name,
            'items_count' => count($event->items),
        ]);

        // Send — MailService checks if email is configured and enabled inside
        MailService::sendLowStockAlert($branch, $event->items);
    }
}