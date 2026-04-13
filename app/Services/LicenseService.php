<?php

namespace App\Services;

use Illuminate\Support\Facades\{Http, Log};
use App\Models\License;

class LicenseService
{
    // How many hours between server re-checks (avoids hammering the API)
    const VERIFY_INTERVAL_HOURS = 24;

    // Grace period after expiry before we hard-block (hours)
    const GRACE_PERIOD_HOURS = 48;

    // License server base URL (set in .env: LICENSE_SERVER_URL=https://license.omnipos.app)
    private string $serverUrl;

    public function __construct()
    {
        $this->serverUrl = rtrim(config('license.server_url', ''), '/');
    }

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Activate a new license key entered by the user.
     * Contacts the server, stores the result locally.
     */
    public function activate(string $key): array
    {
        $key = strtoupper(trim($key));

        // Test mode: accept any valid-looking key without server contact
        if (config('license.test_mode')) {
            if (!preg_match('/^OMNI-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key)) {
                return [
                    'success' => false,
                    'code'    => 'INVALID_FORMAT',
                    'message' => 'License key must be in format: OMNI-XXXX-XXXX-XXXX-XXXX',
                ];
            }

            // Mock successful activation for testing
            $this->storeLicense([
                'plan'           => 'Test Plan',
                'expires_at'     => now()->addDays(30)->toDateTimeString(),
                'days_remaining' => 30,
                'token'          => 'test-token-' . time(),
            ], $key);

            return [
                'success'        => true,
                'plan'           => 'Test Plan',
                'expires_at'     => now()->addDays(30)->format('d M Y'),
                'days_remaining' => 30,
            ];
        }

        $payload = [
            'license_key' => $key,
            'shop_domain' => request()->getHost(),
            'shop_name'   => optional(auth()->user()->shop)->name,
        ];

        Log::debug('LicenseService::activate sending request', [
            'payload' => [
                'license_key_tail' => substr($key, -6),
                'shop_domain'      => $payload['shop_domain'],
                'shop_name'        => $payload['shop_name'],
            ],
            'server_url' => $this->serverUrl,
        ]);

        try {
            $response = Http::timeout(15)
                ->post("{$this->serverUrl}/api/license/activate", $payload);

            $data = $response->json();

            Log::debug('LicenseService::activate response', [
                'status' => $response->status(),
                'body'   => $data,
            ]);

            if (!$response->successful() || !($data['success'] ?? false)) {
                Log::warning('LicenseService::activate server rejected key', [
                    'status' => $response->status(),
                    'body'   => $data,
                ]);

                return [
                    'success' => false,
                    'code'    => $data['code']    ?? 'SERVER_ERROR',
                    'message' => $data['message'] ?? 'Could not activate license. Please try again.',
                ];
            }

            // Store / update locally
            $this->storeLicense($data, $key);

            return [
                'success'        => true,
                'plan'           => $data['plan'],
                'expires_at'     => $data['expires_at'],
                'days_remaining' => $data['days_remaining'],
            ];

        } catch (\Throwable $e) {
            Log::error("LicenseService::activate failed: " . $e->getMessage());
            return [
                'success' => false,
                'code'    => 'NETWORK_ERROR',
                'message' => 'Could not reach the license server. Check your internet connection.',
            ];
        }
    }

    /**
     * Check if the current license is valid.
     * Uses the local cache first; re-verifies with server every 24 hours.
     *
     * Returns true if valid, false if expired/invalid.
     */
    public function isValid(): bool
    {
        $license = $this->getLocalLicense();

        if (!$license) return false;

        // Hard-expired past grace period — block immediately
        if ($license->expires_at && $license->expires_at->addHours(self::GRACE_PERIOD_HOURS)->isPast()) {
            return false;
        }

        // Check if we need to re-verify with the server
        $needsServerCheck = !$license->verified_at
            || $license->verified_at->addHours(self::VERIFY_INTERVAL_HOURS)->isPast();

        if ($needsServerCheck) {
            $this->refreshFromServer($license);
            $license->refresh();
        }

        return $license->status === 'active' && $license->expires_at?->isFuture();
    }

    /**
     * Get the current license details for display.
     */
    public function details(): ?License
    {
        return $this->getLocalLicense();
    }

    /**
     * Get the status for the UI (used on the activation/license page).
     */
    public function status(): array
    {
        $license = $this->getLocalLicense();

        if (!$license) {
            return ['status' => 'none', 'message' => 'No license activated.'];
        }

        if ($license->expires_at?->isPast()) {
            return [
                'status'     => 'expired',
                'message'    => 'License expired on ' . $license->expires_at->format('d M Y'),
                'expired_at' => $license->expires_at->toIso8601String(),
                'plan'       => $license->plan_name,
                'buy_url'    => config('license.buy_url'),
            ];
        }

        return [
            'status'         => 'active',
            'plan'           => $license->plan_name,
            'expires_at'     => $license->expires_at->format('d M Y'),
            'days_remaining' => $license->days_remaining,
            'verified_at'    => $license->verified_at?->format('d M Y H:i'),
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function getLocalLicense(): ?License
    {
        return License::orderByDesc('activated_at')->first();
    }

    private function refreshFromServer(License $license): void
    {
        try {
            $response = Http::timeout(10)
                ->post("{$this->serverUrl}/api/license/verify", [
                    'license_key' => $license->license_key,
                    'shop_domain' => request()->getHost(),
                ]);

            $data = $response->json();

            if ($response->successful() && ($data['valid'] ?? false)) {
                $license->update([
                    'status'              => 'active',
                    'days_remaining'      => $data['days_remaining'] ?? 0,
                    'expires_at'          => $data['expires_at'],
                    'verification_token'  => $data['token'] ?? null,
                    'verified_at'         => now(),
                ]);
            } else {
                $code = $data['code'] ?? 'UNKNOWN';
                $status = match ($code) {
                    'EXPIRED'    => 'expired',
                    'KEY_REVOKED', 'KEY_SUSPENDED' => 'suspended',
                    default      => 'invalid',
                };
                $license->update(['status' => $status, 'verified_at' => now()]);
            }
        } catch (\Throwable $e) {
            // Network error — don't invalidate the license locally.
            // Update verified_at so we don't retry every single request.
            $license->update(['verified_at' => now()]);
            Log::warning("LicenseService: server check failed (network): " . $e->getMessage());
        }
    }

    private function storeLicense(array $data, string $key): void
    {
        License::updateOrCreate(
            ['license_key' => $key],
            [
                'plan_name'          => $data['plan']           ?? null,
                'status'             => 'active',
                'activated_at'       => now(),
                'expires_at'         => $data['expires_at']     ?? null,
                'days_remaining'     => $data['days_remaining'] ?? 0,
                'verification_token' => $data['token']          ?? null,
                'verified_at'        => now(),
            ]
        );
    }
}