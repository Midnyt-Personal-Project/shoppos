<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\{Branch, ShopSetting};
use Symfony\Component\Mailer\{Mailer, Transport};
use Symfony\Component\Mime\{Address, Email};

class MailService
{
    /**
     * Send an email using the branch's configured Gmail account.
     *
     * @param  Branch  $branch
     * @param  string  $toEmail      Recipient email address
     * @param  string  $toName       Recipient display name
     * @param  string  $subject
     * @param  string  $htmlBody     Full HTML content of the email
     * @param  string|null $textBody  Plain-text fallback (auto-stripped if null)
     * @return array   ['success' => bool, 'message' => string]
     */
    public static function sendFromBranch(
        Branch $branch,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): array {
        $config = ShopSetting::branchMailConfig($branch->shop_id, $branch->id);

        if (empty($config['gmail_address']) || empty($config['gmail_password'])) {
            return [
                'success' => false,
                'message' => "No Gmail configured for branch \"{$branch->name}\". Go to Settings → Email to add it.",
            ];
        }

        if (! $config['enabled']) {
            return [
                'success' => false,
                'message' => "Email is disabled for branch \"{$branch->name}\".",
            ];
        }

        try {
            // Build Symfony Mailer transport using Gmail SMTP + App Password
            $dsn = sprintf(
                'smtps://%s:%s@smtp.gmail.com:465',
                urlencode($config['gmail_address']),
                urlencode($config['gmail_password'])
            );

            $transport = Transport::fromDsn($dsn);
            $mailer    = new Mailer($transport);

            $fromName = $config['from_name'] ?: ($branch->shop->name . ' — ' . $branch->name);

            $email = (new Email())
                ->from(new Address($config['gmail_address'], $fromName))
                ->to(new Address($toEmail, $toName))
                ->subject($subject)
                ->html($htmlBody)
                ->text($textBody ?? strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));

            $mailer->send($email);

            Log::info("OmniPOS Mail sent", [
                'branch'  => $branch->name,
                'from'    => $config['gmail_address'],
                'to'      => $toEmail,
                'subject' => $subject,
            ]);

            return ['success' => true, 'message' => "Email sent successfully from {$config['gmail_address']}."];

        } catch (\Throwable $e) {
            Log::error("OmniPOS Mail failed", [
                'branch' => $branch->name,
                'error'  => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a test email to verify the branch Gmail config works.
     */
    public static function sendTestEmail(Branch $branch, string $toEmail): array
    {
        $html = self::buildTestEmailHtml($branch);

        return self::sendFromBranch(
            branch:   $branch,
            toEmail:  $toEmail,
            toName:   'OmniPOS Test',
            subject:  'OmniPOS ✅ Email Test — ' . $branch->name,
            htmlBody: $html,
        );
    }

    /**
     * Send a low-stock alert email.
     */
    public static function sendLowStockAlert(Branch $branch, array $lowStockItems): array
    {
        Log::debug('MailService::sendLowStockAlert called', [
            'branch' => $branch->name,
            'items_count' => count($lowStockItems),
        ]);

        $config = ShopSetting::notificationsFor($branch->shop_id);
        if (! $config['notify_low_stock']) {
            Log::info('MailService::sendLowStockAlert: Low stock notifications disabled');
            return ['success' => false, 'message' => 'Low stock notifications are disabled.'];
        }

        $mailConfig = ShopSetting::branchMailConfig($branch->shop_id, $branch->id);
        if (empty($mailConfig['gmail_address'])) {
            Log::info('MailService::sendLowStockAlert: No email configured', ['branch' => $branch->name]);
            return ['success' => false, 'message' => 'No email configured for this branch.'];
        }

        $rows = '';
        foreach ($lowStockItems as $item) {
            $status = $item['qty'] <= 0 ? 'Out of Stock' : 'Low Stock';
            $color  = $item['qty'] <= 0 ? '#DC2626' : '#D97706';
            $rows  .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;'>{$item['name']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;text-align:center;font-weight:bold;color:{$color};'>{$item['qty']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;text-align:center;'>{$item['alert']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;text-align:center;color:{$color};font-weight:bold;'>{$status}</td>
            </tr>";
        }

        $count = count($lowStockItems);
        $html  = self::emailTemplate(
            branch:  $branch,
            title:   '⚠️ Low Stock Alert',
            content: "
            <p style='color:#475569;font-size:15px;margin:0 0 20px;'>
                <strong>{$count} product(s)</strong> at <strong>{$branch->name}</strong> need restocking:
            </p>
            <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                <thead>
                    <tr style='background:#f1f5f9;'>
                        <th style='padding:10px 12px;text-align:left;color:#64748b;font-weight:600;'>Product</th>
                        <th style='padding:10px 12px;text-align:center;color:#64748b;font-weight:600;'>Current Stock</th>
                        <th style='padding:10px 12px;text-align:center;color:#64748b;font-weight:600;'>Alert Level</th>
                        <th style='padding:10px 12px;text-align:center;color:#64748b;font-weight:600;'>Status</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>
            <p style='color:#94a3b8;font-size:13px;margin:20px 0 0;'>Please restock these items as soon as possible.</p>
            "
        );

        return self::sendFromBranch(
            branch:   $branch,
            toEmail:  $mailConfig['gmail_address'],
            toName:   $branch->name,
            subject:  "⚠️ Low Stock Alert — {$branch->name} ({$count} items)",
            htmlBody: $html,
        );
    }

    /**
     * Send a daily sales summary email.
     */
    public static function sendDailySummary(Branch $branch, array $data): array
    {
        Log::debug('MailService::sendDailySummary called', [
            'branch' => $branch->name,
        ]);

        $config = ShopSetting::notificationsFor($branch->shop_id);
        if (! $config['notify_daily_summary']) {
            Log::info('MailService::sendDailySummary: Daily summary notifications disabled');
            return ['success' => false, 'message' => 'Daily summary notifications are disabled.'];
        }

        $mailConfig = ShopSetting::branchMailConfig($branch->shop_id, $branch->id);
        if (empty($mailConfig['gmail_address'])) {
            Log::info('MailService::sendDailySummary: No email configured', ['branch' => $branch->name]);
            return ['success' => false, 'message' => 'No email configured for this branch.'];
        }

        $currency = $branch->shop->currency_symbol;
        $date     = now()->format('l, d F Y');

        $html = self::emailTemplate(
            branch:  $branch,
            title:   "📊 Daily Sales Summary",
            content: "
            <p style='color:#475569;font-size:15px;margin:0 0 20px;'>{$date} — <strong>{$branch->name}</strong></p>
            <table style='width:100%;border-collapse:collapse;font-size:15px;margin-bottom:20px;'>
                <tr style='background:#f0fdf4;'>
                    <td style='padding:14px 16px;border-radius:8px 0 0 8px;color:#15803d;font-weight:600;'>💰 Total Revenue</td>
                    <td style='padding:14px 16px;border-radius:0 8px 8px 0;color:#15803d;font-weight:700;font-size:20px;text-align:right;'>{$currency}" . number_format($data['revenue'], 2) . "</td>
                </tr>
                <tr><td colspan='2' style='padding:4px;'></td></tr>
                <tr style='background:#f8fafc;'>
                    <td style='padding:12px 16px;color:#475569;'>🧾 Number of Sales</td>
                    <td style='padding:12px 16px;color:#0f172a;font-weight:600;text-align:right;'>" . ($data['count'] ?? 0) . " transactions</td>
                </tr>
                <tr style='background:#f8fafc;'>
                    <td style='padding:12px 16px;color:#475569;'>📦 Cost of Goods</td>
                    <td style='padding:12px 16px;color:#0f172a;font-weight:600;text-align:right;'>{$currency}" . number_format($data['cogs'] ?? 0, 2) . "</td>
                </tr>
                <tr style='background:#f8fafc;'>
                    <td style='padding:12px 16px;color:#475569;'>💸 Expenses</td>
                    <td style='padding:12px 16px;color:#0f172a;font-weight:600;text-align:right;'>{$currency}" . number_format($data['expenses'] ?? 0, 2) . "</td>
                </tr>
                <tr style='background:#faf5ff;'>
                    <td style='padding:14px 16px;color:#7c3aed;font-weight:600;'>✨ Net Profit</td>
                    <td style='padding:14px 16px;color:#7c3aed;font-weight:700;font-size:18px;text-align:right;'>{$currency}" . number_format($data['profit'] ?? 0, 2) . "</td>
                </tr>
            </table>
            " . (isset($data['top_product']) ? "<p style='color:#64748b;font-size:13px;margin:0;'>🏆 Best seller today: <strong style='color:#0f172a;'>{$data['top_product']}</strong></p>" : "")
        );

        return self::sendFromBranch(
            branch:   $branch,
            toEmail:  $mailConfig['gmail_address'],
            toName:   $branch->name,
            subject:  "📊 Daily Summary — {$branch->name} | {$currency}" . number_format($data['revenue'], 2) . " revenue",
            htmlBody: $html,
        );
    }

    // ── Private builders ──────────────────────────────────────────────────────

    private static function buildTestEmailHtml(Branch $branch): string
    {
        return self::emailTemplate(
            branch:  $branch,
            title:   '✅ Email Configuration Working!',
            content: "
            <p style='color:#475569;font-size:15px;margin:0 0 16px;'>
                Great news — the Gmail account for <strong>{$branch->name}</strong> is correctly configured and working.
            </p>
            <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;margin-bottom:20px;'>
                <p style='margin:0;color:#15803d;font-size:14px;'>
                    📧 Emails will be sent <strong>from</strong> this Gmail account for all notifications related to <strong>{$branch->name}</strong>.
                </p>
            </div>
            <p style='color:#94a3b8;font-size:13px;margin:0;'>
                You can now enable notifications in Settings → Notifications.
            </p>
            "
        );
    }

    private static function emailTemplate(Branch $branch, string $title, string $content): string
    {
        $shopName   = $branch->shop->name ?? 'OmniPOS';
        $branchName = $branch->name;
        $year       = date('Y');

        return "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",sans-serif;'>
  <div style='max-width:600px;margin:32px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);'>

    <!-- Header -->
    <div style='background:#14532d;padding:28px 32px;'>
      <p style='margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;'>{$shopName}</p>
      <p style='margin:4px 0 0;font-size:13px;color:#86efac;'>{$branchName}</p>
    </div>

    <!-- Title bar -->
    <div style='background:#16a34a;padding:16px 32px;'>
      <p style='margin:0;font-size:18px;font-weight:600;color:#ffffff;'>{$title}</p>
    </div>

    <!-- Body -->
    <div style='padding:32px;'>
      {$content}
    </div>

    <!-- Footer -->
    <div style='background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 32px;'>
      <p style='margin:0;font-size:12px;color:#94a3b8;text-align:center;'>
        Sent by <strong>OmniPOS</strong> on behalf of {$shopName} — {$branchName}<br>
        &copy; {$year} OmniPOS. This is an automated message.
      </p>
    </div>

  </div>
</body>
</html>";
    }
}