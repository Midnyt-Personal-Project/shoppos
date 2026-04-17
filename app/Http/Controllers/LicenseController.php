<?php

namespace App\Http\Controllers;

use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LicenseController extends Controller
{
    public function __construct(private LicenseService $license) {}

    /**
     * The license activation / status page.
     */
    public function index()
    {
        $status  = $this->license->status();
        $details = $this->license->details();
        $buyUrl  = config('license.buy_url', '#');
        $shopName = auth()->user()->shop_name ?? '';

        return view('license.index', compact('status', 'details', 'buyUrl', 'shopName'));
    }

    /**
     * Handle the activate form submission.
     */
    public function activate(Request $request)
    {
        
        
        $request->validate([
            'license_key' => 'required|string|min:10',
            'shop_name' => 'nullable|string|max:255',
        ]);

        Log::info('LicenseController::activate called', [
            'user_id' => auth()->id(),
            'license_key_tail' => substr($request->license_key, -6),
            'route' => $request->path(),
        ]);

        $result = $this->license->activate($request->license_key);

        Log::info('LicenseController::activate result', [
            'user_id' => auth()->id(),
            'license_key_tail' => substr($request->license_key, -6),
            'result' => $result,
        ]);

        if (!$result['success']) {
            Log::warning('LicenseController::activate failed', [
                'user_id' => auth()->id(),
                'license_key_tail' => substr($request->license_key, -6),
                'result' => $result,
            ]);

            $message = match ($result['code'] ?? '') {
                'INVALID_KEY'     => 'This license key does not exist. Check for typos.',
                'KEY_REVOKED'     => 'This license key has been revoked. Please contact support.',
                'KEY_SUSPENDED'   => 'This license is suspended. Please contact support.',
                'KEY_EXPIRED'     => 'This license key has already expired. Please purchase a new one.',
                'DOMAIN_MISMATCH' => 'This key is already activated on a different domain.',
                'NETWORK_ERROR'   => 'Cannot reach the license server. Check your internet connection and try again.',
                default           => $result['message'] ?? 'Activation failed. Please try again.',
            };

            return back()
                ->withInput()
                ->with('error', $message);
        }

        Log::info('LicenseController::activate succeeded', [
            'user_id' => auth()->id(),
            'license_key_tail' => substr($request->license_key, -6),
            'plan' => $result['plan'] ?? null,
            'expires_at' => $result['expires_at'] ?? null,
        ]);

        return redirect()->route('dashboard')
            ->with('success', "✅ License activated! Plan: {$result['plan']}. Expires: " . date('d M Y', strtotime($result['expires_at'])));
    }

    /**
     * Force a re-check with the server (useful for debugging / manual refresh).
     */
    public function refresh()
    {
        // Wipe the verified_at so the middleware will re-check on next request
        \App\Models\License::query()->update(['verified_at' => null]);

        return redirect()->route('license.index')
            ->with('success', 'License status refreshed from server.');
    }
}