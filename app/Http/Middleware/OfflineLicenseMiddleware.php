<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Closure;

use App\Models\ShopSetting;

class OfflineLicenseMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        Log::info('OfflineLicenseMiddleware: Incoming request execution started', [
            'route_name' => $request->route()?->getName(),
            'uri'        => $request->path(),
            'full_url'   => $request->fullUrl(),
            'method'     => $request->method(),
        ]);

        // Forcefully bypass settings, license, login, logout, and setup entirely by segment check
        $uri = trim($request->path(), '/');
        if (str_starts_with($uri, 'settings') || str_starts_with($uri, 'license') || str_starts_with($uri, 'setup') || $uri === 'login' || $uri === 'logout') {
            Log::info('OfflineLicenseMiddleware: Request bypassed successfully via string matching', [
                'uri' => $uri,
            ]);
            return $next($request);
        }

        Log::debug('OfflineLicenseMiddleware: Route was NOT bypassed, checking authentication and period validation.');

        if (auth()->check()) {
            $shopId = auth()->user()->shop_id ?? 1;
            $currentYear = (string) date('Y');
            $currentMonth = (int) date('n');

            $allowedYears = ShopSetting::get($shopId, 'offline_allowed_years');
            $allowedMonths = ShopSetting::get($shopId, 'offline_allowed_months');

            if (is_null($allowedYears)) {
                $allowedYears = [$currentYear];
            } else {
                if (is_string($allowedYears)) {
                    $allowedYears = array_filter(array_map('trim', explode(',', $allowedYears)));
                }
            }

            if (is_null($allowedMonths)) {
                $allowedMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            } else {
                if (is_string($allowedMonths)) {
                    $allowedMonths = array_filter(array_map('intval', explode(',', $allowedMonths)));
                }
            }

            $allowedYears = array_map('strval', (array) $allowedYears);
            $allowedMonths = array_map('intval', (array) $allowedMonths);

            $yearAllowed = in_array($currentYear, $allowedYears, true);
            $monthAllowed = in_array($currentMonth, $allowedMonths, true);

            if (!$yearAllowed || !$monthAllowed) {
                Log::warning('OfflineLicenseMiddleware: Access denied, offline license expired/invalid for this period', [
                    'route'          => $request->route()?->getName(),
                    'uri'            => $request->path(),
                    'current_year'   => $currentYear,
                    'current_month'  => $currentMonth,
                    'allowed_years'  => $allowedYears,
                    'allowed_months' => $allowedMonths,
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message'  => 'Your offline software license is not active for this period.',
                        'code'     => 'LICENSE_INVALID_PERIOD',
                        'redirect' => route('license.index'),
                    ], 402);
                }

                return redirect()->route('license.index')
                    ->with('warning', 'Your offline software license is not active for this period. Please contact your administrator.');
            }
        }

        return $next($request);
    }
}