<?php

namespace App\Listeners;

use App\Events\SaleCompleted;
use App\Models\ShopSetting;
use App\Services\MailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendSaleNotification implements ShouldQueue
{
    public function handle(SaleCompleted $event): void
    {
        $sale   = $event->sale->load(['branch.shop', 'items', 'user', 'customer']);
        $branch = $sale->branch;
        $shopId = $branch->shop_id;

        Log::info('SendSaleNotification: Sale notification triggered', [
            'sale_id' => $sale->id,
            'branch' => $branch->name,
            'reference' => $sale->reference,
        ]);

        // Check toggle
        $notifyEnabled = ShopSetting::get($shopId, 'notify_new_sale', false);
        Log::debug('SendSaleNotification: Checking notify_new_sale toggle', [
            'shop_id' => $shopId,
            'notify_new_sale' => $notifyEnabled,
        ]);
        
        if (! $notifyEnabled) {
            Log::info('SendSaleNotification: Skipped - notify_new_sale is disabled');
            return;
        }

        // Check branch email configured and enabled
        $mailConfig = ShopSetting::branchMailConfig($shopId, $branch->id);
        
        Log::debug('SendSaleNotification: Mail config retrieved', [
            'branch_id' => $branch->id,
            'has_gmail' => !empty($mailConfig['gmail_address']),
            'enabled' => $mailConfig['enabled'] ?? false,
            'gmail_address' => $mailConfig['gmail_address'] ?? 'not set',
        ]);
        
        if (empty($mailConfig['gmail_address']) || ! $mailConfig['enabled']) {
            Log::info('SendSaleNotification: Skipped - Mail not configured or disabled', [
                'reason' => empty($mailConfig['gmail_address']) ? 'no_gmail' : 'disabled',
            ]);
            return;
        }

        $currency = $branch->shop->currency_symbol;

        // Build items HTML
        $itemsHtml = '';
        foreach ($sale->items as $item) {
            $itemsHtml .= "<tr>
                <td style='padding:6px 12px;border-bottom:1px solid #e2e8f0;'>{$item->product_name}</td>
                <td style='padding:6px 12px;border-bottom:1px solid #e2e8f0;text-align:center;'>{$item->quantity}</td>
                <td style='padding:6px 12px;border-bottom:1px solid #e2e8f0;text-align:right;'>{$currency}" . number_format($item->total, 2) . "</td>
            </tr>";
        }

        $customerName = $sale->customer?->name ?? 'Walk-in';
        $cashierName  = $sale->user->name;
        $paymentStatus = ucfirst($sale->payment_status);
        $time = $sale->created_at->format('h:i A');

        $html = self::buildHtml($branch, $sale, $currency, $itemsHtml, $customerName, $cashierName, $paymentStatus, $time);

        Log::info('SendSaleNotification: Sending mail', [
            'sale_id' => $sale->id,
            'to_email' => $mailConfig['gmail_address'],
            'subject_preview' => "🧾 New Sale {$sale->reference} — {$currency}" . number_format($sale->total, 2),
        ]);

        $result = MailService::sendFromBranch(
            branch:   $branch,
            toEmail:  $mailConfig['gmail_address'],
            toName:   $branch->name,
            subject:  "🧾 New Sale {$sale->reference} — {$currency}" . number_format($sale->total, 2) . " [{$branch->name}]",
            htmlBody: $html,
        );

        if ($result['success']) {
            Log::info('SendSaleNotification: Mail sent successfully', ['sale_id' => $sale->id]);
        } else {
            Log::error('SendSaleNotification: Mail send failed', [
                'sale_id' => $sale->id,
                'error' => $result['message'],
            ]);
        }
    }

    private static function buildHtml($branch, $sale, $currency, $itemsHtml, $customerName, $cashierName, $paymentStatus, $time): string
    {
        $shopName = $branch->shop->name;
        $year     = date('Y');

        return "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,sans-serif;'>
<div style='max-width:560px;margin:28px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.07);'>
  <div style='background:#14532d;padding:22px 28px;'>
    <p style='margin:0;font-size:18px;font-weight:700;color:#fff;'>{$shopName}</p>
    <p style='margin:4px 0 0;font-size:12px;color:#86efac;'>{$branch->name}</p>
  </div>
  <div style='background:#16a34a;padding:12px 28px;'>
    <p style='margin:0;font-size:15px;font-weight:600;color:#fff;'>🧾 New Sale Completed</p>
  </div>
  <div style='padding:24px 28px;'>
    <table style='width:100%;border-collapse:collapse;margin-bottom:20px;font-size:13px;'>
      <tr><td style='padding:5px 0;color:#64748b;'>Reference</td><td style='padding:5px 0;font-weight:600;color:#0f172a;text-align:right;font-family:monospace;'>{$sale->reference}</td></tr>
      <tr><td style='padding:5px 0;color:#64748b;'>Time</td><td style='padding:5px 0;color:#0f172a;text-align:right;'>{$time}</td></tr>
      <tr><td style='padding:5px 0;color:#64748b;'>Cashier</td><td style='padding:5px 0;color:#0f172a;text-align:right;'>{$cashierName}</td></tr>
      <tr><td style='padding:5px 0;color:#64748b;'>Customer</td><td style='padding:5px 0;color:#0f172a;text-align:right;'>{$customerName}</td></tr>
      <tr><td style='padding:5px 0;color:#64748b;'>Payment</td><td style='padding:5px 0;color:#0f172a;text-align:right;'>{$paymentStatus}</td></tr>
    </table>
    <table style='width:100%;border-collapse:collapse;font-size:13px;margin-bottom:16px;'>
      <thead><tr style='background:#f8fafc;'>
        <th style='padding:8px 12px;text-align:left;color:#64748b;font-weight:600;'>Item</th>
        <th style='padding:8px 12px;text-align:center;color:#64748b;font-weight:600;'>Qty</th>
        <th style='padding:8px 12px;text-align:right;color:#64748b;font-weight:600;'>Total</th>
      </tr></thead>
      <tbody>{$itemsHtml}</tbody>
    </table>
    <div style='background:#f0fdf4;border-radius:8px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;'>
      <span style='color:#15803d;font-weight:600;font-size:14px;'>Grand Total</span>
      <span style='color:#15803d;font-weight:700;font-size:20px;'>{$currency}" . number_format($sale->total, 2) . "</span>
    </div>
    " . ($sale->balance_due > 0 ? "<p style='color:#dc2626;font-size:13px;margin:10px 0 0;'>⚠️ Balance due: {$currency}" . number_format($sale->balance_due, 2) . "</p>" : "") . "
  </div>
  <div style='background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 28px;text-align:center;'>
    <p style='margin:0;font-size:11px;color:#94a3b8;'>OmniPOS · {$shopName} · {$branch->name} · &copy;{$year}</p>
  </div>
</div></body></html>";
    }
}