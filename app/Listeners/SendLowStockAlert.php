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
    $shop = $branch->shop;
    $shopId = $shop->id;

    Log::info('SendLowStockAlert: Stock low alert triggered', [
        'branch' => $branch->name,
        'items_count' => count($event->items),
    ]);

    // Check toggle
    $notifyEnabled = ShopSetting::get($shopId, 'notify_low_stock', true);
    if (! $notifyEnabled) {
        Log::info('SendLowStockAlert: Skipped - notify_low_stock is disabled');
        return;
    }

    // Send to shop email
    $recipientEmail = $shop->email;
    if (empty($recipientEmail)) {
        Log::error('SendLowStockAlert: Shop has no email', ['shop_id' => $shopId]);
        return;
    }

    Log::info('SendLowStockAlert: Sending alert mail', [
        'branch' => $branch->name,
        'items_count' => count($event->items),
        'to' => $recipientEmail,
    ]);

    // Pass the shop email to MailService
    MailService::sendLowStockAlert($branch, $event->items, $recipientEmail);
}
}