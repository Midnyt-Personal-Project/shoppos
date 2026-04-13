<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Branch, Expense, Sale, SaleItem, ShopSetting};
use App\Services\MailService;

class SendDailySummaryEmail extends Command
{
    protected $signature   = 'omnipos:daily-summary';
    protected $description = 'Send daily sales summary emails to all branches that have email configured.';

    public function handle(): void
    {
        $this->info('Sending daily summary emails...');

        // Get every active branch across all shops
        $branches = Branch::with('shop')->where('is_active', true)->get();

        foreach ($branches as $branch) {
            $shopId = $branch->shop_id;

            // Check global toggle
            if (! ShopSetting::get($shopId, 'notify_daily_summary', true)) continue;

            // Check branch email configured and enabled
            $mailConfig = ShopSetting::branchMailConfig($shopId, $branch->id);
            if (empty($mailConfig['gmail_address']) || ! $mailConfig['enabled']) continue;

            // Build today's data
            $salesQuery = Sale::where('branch_id', $branch->id)
                ->whereDate('created_at', today())
                ->where('status', 'completed');

            $sales    = (clone $salesQuery)->with('items')->get();
            $revenue  = $sales->sum('total');
            $count    = $sales->count();
            $cogs     = $sales->flatMap->items->sum(fn($i) => $i->cost * $i->quantity);
            $expenses = Expense::where('branch_id', $branch->id)
                               ->whereDate('expense_date', today())
                               ->sum('amount');
            $profit   = $revenue - $cogs - $expenses;

            // Top product today
            $topProduct = SaleItem::selectRaw('product_name, SUM(quantity) as qty')
                ->whereIn('sale_id', $sales->pluck('id'))
                ->groupBy('product_name')
                ->orderByDesc('qty')
                ->value('product_name');

            $result = MailService::sendDailySummary($branch, [
                'revenue'     => $revenue,
                'count'       => $count,
                'cogs'        => $cogs,
                'expenses'    => $expenses,
                'profit'      => $profit,
                'top_product' => $topProduct,
            ]);

            $status = $result['success'] ? '✅' : '❌';
            $this->line("  {$status} {$branch->name} — {$result['message']}");
        }

        $this->info('Done.');
    }
}