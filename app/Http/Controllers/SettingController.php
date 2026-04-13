<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Branch, ShopSetting};
use App\Services\MailService;

class SettingController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $shopId   = $user->shop_id;
        $branches = Branch::where('shop_id', $shopId)->where('is_active', true)->get();

        // Shop-level settings
        $shopSettings = [
            'shop_name'    => ShopSetting::get($shopId, 'shop_name',    $user->shop->name),
            'shop_phone'   => ShopSetting::get($shopId, 'shop_phone',   $user->shop->phone),
            'shop_address' => ShopSetting::get($shopId, 'shop_address', $user->shop->address),
            'shop_email'   => ShopSetting::get($shopId, 'shop_email',   $user->shop->email),
            'currency'     => ShopSetting::get($shopId, 'currency',     $user->shop->currency),
            'currency_symbol' => ShopSetting::get($shopId, 'currency_symbol', $user->shop->currency_symbol),
        ];

        // Notification toggles
        $notifications = ShopSetting::notificationsFor($shopId);

        // Per-branch email configs
        $branchMailConfigs = ShopSetting::allBranchMailConfigs($shopId);

        return view('settings.index', compact(
            'branches', 'shopSettings', 'notifications', 'branchMailConfigs'
        ));
    }

    /** Save general shop settings */
    public function saveGeneral(Request $request)
    {
        $user   = auth()->user();
        $shopId = $user->shop_id;

        $data = $request->validate([
            'shop_name'       => 'required|string|max:255',
            'shop_phone'      => 'nullable|string|max:30',
            'shop_address'    => 'nullable|string|max:500',
            'shop_email'      => 'nullable|email|max:255',
            'currency'        => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:5',
        ]);

        foreach ($data as $key => $value) {
            ShopSetting::set($shopId, $key, $value ?? '');
        }

        // Also update the shop model directly
        $user->shop->update([
            'name'            => $data['shop_name'],
            'phone'           => $data['shop_phone'],
            'address'         => $data['shop_address'],
            'email'           => $data['shop_email'],
            'currency'        => $data['currency'],
            'currency_symbol' => $data['currency_symbol'],
        ]);

        return redirect()->route('settings.index')->with('success', 'General settings saved.');
    }

    /** Save notification toggle settings */
    public function saveNotifications(Request $request)
    {
        $shopId = auth()->user()->shop_id;

        $toggles = [
            'notify_low_stock',
            'notify_new_sale',
            'notify_daily_summary',
            'notify_debt_reminder',
        ];

        foreach ($toggles as $toggle) {
            ShopSetting::set($shopId, $toggle, $request->boolean($toggle), 'boolean');
        }

        return redirect()->route('settings.index')->with('success', 'Notification preferences saved.');
    }

    /** Save email (Gmail) config for a specific branch */
    public function saveBranchEmail(Request $request, Branch $branch)
    {
        $user = auth()->user();

        if ($branch->shop_id !== $user->shop_id) abort(403);

        $data = $request->validate([
            'mail_gmail_address'      => 'required|email|max:255',
            'mail_gmail_app_password' => 'required|string|min:8|max:100',
            'mail_from_name'          => 'nullable|string|max:100',
            'mail_enabled'            => 'boolean',
        ]);

        ShopSetting::setBranch($user->shop_id, $branch->id, 'mail_gmail_address',      $data['mail_gmail_address']);
        ShopSetting::setBranch($user->shop_id, $branch->id, 'mail_gmail_app_password', $data['mail_gmail_app_password']);
        ShopSetting::setBranch($user->shop_id, $branch->id, 'mail_from_name',          $data['mail_from_name'] ?? $branch->name);
        ShopSetting::setBranch($user->shop_id, $branch->id, 'mail_enabled',            $request->boolean('mail_enabled'), 'boolean');

        return redirect()->route('settings.index')->with('success', "Email settings saved for {$branch->name}.");
    }

    /** Send a test email for a branch — returns JSON */
    public function testEmail(Request $request, Branch $branch)
    {
        $user = auth()->user();
        if ($branch->shop_id !== $user->shop_id) abort(403);

        $request->validate(['test_to' => 'required|email']);

        $result = MailService::sendTestEmail($branch, $request->test_to);

        return response()->json($result);
    }

    /** Clear the email config for a branch */
    public function clearBranchEmail(Branch $branch)
    {
        $user = auth()->user();
        if ($branch->shop_id !== $user->shop_id) abort(403);

        \App\Models\ShopSetting::where('shop_id', $user->shop_id)
            ->where('branch_id', $branch->id)
            ->whereIn('key', ['mail_gmail_address', 'mail_gmail_app_password', 'mail_from_name', 'mail_enabled'])
            ->delete();

        return redirect()->route('settings.index')->with('success', "Email config removed for {$branch->name}.");
    }
}