<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\LicenseService;

class LicenseMiddleware
{
    public function __construct(private LicenseService $license) {}

    public function handle(Request $request, Closure $next): mixed
    {
        Log::debug('LicenseMiddleware: handling route', [
            'route'      => $request->route()?->getName(),
            'uri'        => $request->path(),
            'has_license'=> (bool)$this->license->details(),
        ]);

        // Always allow: login, logout, setup, license activation routes
        $bypassed = [
            'login', 'logout', 'setup.*', 'setup.check', 'setup.store',
            'license.*',
        ];

        foreach ($bypassed as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        if (!$this->license->isValid()) {
            $details = $this->license->details();
            Log::warning('LicenseMiddleware: access denied, invalid or missing license', [
                'route'   => $request->route()?->getName(),
                'uri'     => $request->path(),
                'details' => $details?->toArray(),
            ]);

            // If it's an AJAX request, return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'message'  => 'License expired or invalid.',
                    'code'     => 'LICENSE_INVALID',
                    'redirect' => route('license.index'),
                ], 402);
            }

            // Otherwise redirect to the license page
            if ($details && $details->expires_at?->isPast()) {
                return redirect()->route('license.index')
                    ->with('warning', 'Your OmniPOS license has expired. Please renew to continue.');
            }

            return redirect()->route('license.index')
                ->with('warning', 'Please activate your OmniPOS license to continue.');
        }

        // Attach a low-days warning to the session (shows in the UI header)
        $details = $this->license->details();
        if ($details && $details->days_remaining <= 7 && $details->days_remaining > 0) {
            session()->flash('license_warning',
                "⚠️ Your license expires in {$details->days_remaining} day(s). "
                . '<a href="' . config('license.buy_url') . '" target="_blank" class="underline">Renew now →</a>'
            );
        }

        return $next($request);
    }
}