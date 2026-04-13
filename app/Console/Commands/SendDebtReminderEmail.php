<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Branch, Customer, Shop, ShopSetting};
use App\Services\MailService;

class SendDebtReminderEmail extends Command
{
    protected $signature   = 'omnipos:debt-reminder';
    protected $description = 'Send weekly outstanding debt summary to each branch Gmail.';

    public function handle(): void
    {
        $this->info('Sending debt reminder emails...');

        $shops = Shop::with('branches')->where('is_active', true)->get();

        foreach ($shops as $shop) {
            // Check toggle
            if (! ShopSetting::get($shop->id, 'notify_debt_reminder', false)) continue;

            // Get all customers with outstanding debt for this shop
            $debtors = Customer::where('shop_id', $shop->id)
                ->where('outstanding_balance', '>', 0)
                ->orderByDesc('outstanding_balance')
                ->get();

            if ($debtors->isEmpty()) continue;

            // Send to the main branch (or owner branch)
            // In a multi-branch shop, we send to all branches that have email configured
            foreach ($shop->branches as $branch) {
                $mailConfig = ShopSetting::branchMailConfig($shop->id, $branch->id);
                if (empty($mailConfig['gmail_address']) || ! $mailConfig['enabled']) continue;

                $currency    = $shop->currency_symbol;
                $totalDebt   = $debtors->sum('outstanding_balance');
                $debtorCount = $debtors->count();
                $date        = now()->format('l, d F Y');

                // Build rows HTML
                $rows = '';
                foreach ($debtors as $i => $customer) {
                    $bg    = $i % 2 === 0 ? '#ffffff' : '#f8fafc';
                    $rows .= "<tr style='background:{$bg}'>
                        <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;'>" . htmlspecialchars($customer->name) . "</td>
                        <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;'>" . ($customer->phone ?? '—') . "</td>
                        <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:600;color:#dc2626;'>{$currency}" . number_format($customer->outstanding_balance, 2) . "</td>
                    </tr>";
                }

                $html = $this->buildHtml($shop, $branch, $currency, $totalDebt, $debtorCount, $date, $rows);

                $result = MailService::sendFromBranch(
                    branch:   $branch,
                    toEmail:  $mailConfig['gmail_address'],
                    toName:   $branch->name,
                    subject:  "💳 Weekly Debt Report — {$debtorCount} customers owe {$currency}" . number_format($totalDebt, 2),
                    htmlBody: $html,
                );

                $status = $result['success'] ? '✅' : '❌';
                $this->line("  {$status} {$branch->name} — {$result['message']}");

                break; // Only send once per shop (to first configured branch)
            }
        }

        $this->info('Done.');
    }

    private function buildHtml($shop, $branch, $currency, $totalDebt, $debtorCount, $date, $rows): string
    {
        $shopName   = $shop->name;
        $branchName = $branch->name;
        $year       = date('Y');

        return "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,sans-serif;'>
<div style='max-width:600px;margin:28px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.07);'>
  <div style='background:#14532d;padding:22px 28px;'>
    <p style='margin:0;font-size:18px;font-weight:700;color:#fff;'>{$shopName}</p>
    <p style='margin:4px 0 0;font-size:12px;color:#86efac;'>{$branchName}</p>
  </div>
  <div style='background:#dc2626;padding:12px 28px;'>
    <p style='margin:0;font-size:15px;font-weight:600;color:#fff;'>💳 Weekly Outstanding Debt Report</p>
  </div>
  <div style='padding:24px 28px;'>
    <p style='color:#475569;font-size:14px;margin:0 0 16px;'>{$date}</p>
    <div style='display:flex;gap:16px;margin-bottom:24px;'>
      <div style='flex:1;background:#fef2f2;border-radius:10px;padding:14px 16px;'>
        <p style='margin:0;font-size:12px;color:#ef4444;'>Total Outstanding</p>
        <p style='margin:4px 0 0;font-size:22px;font-weight:700;color:#dc2626;'>{$currency}" . number_format($totalDebt, 2) . "</p>
      </div>
      <div style='flex:1;background:#f8fafc;border-radius:10px;padding:14px 16px;'>
        <p style='margin:0;font-size:12px;color:#64748b;'>Customers with Debt</p>
        <p style='margin:4px 0 0;font-size:22px;font-weight:700;color:#0f172a;'>{$debtorCount}</p>
      </div>
    </div>
    <table style='width:100%;border-collapse:collapse;font-size:13px;'>
      <thead><tr style='background:#f1f5f9;'>
        <th style='padding:10px 12px;text-align:left;color:#64748b;font-weight:600;'>Customer</th>
        <th style='padding:10px 12px;text-align:left;color:#64748b;font-weight:600;'>Phone</th>
        <th style='padding:10px 12px;text-align:right;color:#64748b;font-weight:600;'>Amount Owed</th>
      </tr></thead>
      <tbody>{$rows}</tbody>
    </table>
  </div>
  <div style='background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 28px;text-align:center;'>
    <p style='margin:0;font-size:11px;color:#94a3b8;'>OmniPOS · {$shopName} · &copy;{$year}</p>
  </div>
</div></body></html>";
    }
}