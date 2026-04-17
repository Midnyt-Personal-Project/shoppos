<?php

namespace App\Services;

use Illuminate\Support\Facades\{Http, Log};
use App\Models\License;

class LicenseService
{
    // How many hours between server re-checks (optional; set to 0 to check every time)
    const VERIFY_INTERVAL_HOURS = 24;

    private string $serverUrl;

    public function __construct()
    {
        $this->serverUrl = rtrim(config('license.server_url', ''), '/');

        // Log the configuration on service instantiation
        Log::info('LicenseService initialized', [
            'server_url' => $this->serverUrl,
            'test_mode' => config('license.test_mode'),
            'buy_url' => config('license.buy_url'),
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Activate a license key – always calls the license server (unless test mode is on).
     * Returns success only if the server confirms validity.
     */
    public function activate(string $key): array
    {
        $key = strtoupper(trim($key));

        Log::info('License activation started', [
            'key_tail' => substr($key, -6),
            'test_mode' => config('license.test_mode'),
            'full_key_format' => preg_match('/^OMNI-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key) ? 'valid_format' : 'invalid_format',
        ]);

        // Optional test mode (bypass real server)
        if (config('license.test_mode')) {
            Log::warning('⚠️ TEST MODE IS ACTIVE – license validation is bypassed! No server call will be made.', [
                'key' => $key,
                'environment' => app()->environment(),
                'recommendation' => 'Set LICENSE_TEST_MODE=false in .env for production',
            ]);

            if (!preg_match('/^OMNI-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key)) {
                Log::warning('Test mode: invalid key format rejected', ['key' => $key]);
                return [
                    'success' => false,
                    'code'    => 'INVALID_FORMAT',
                    'message' => 'License key must be in format: OMNI-XXXX-XXXX-XXXX-XXXX',
                ];
            }

            // Mock successful activation for testing
            Log::info('Test mode: creating mock license', ['key_tail' => substr($key, -6)]);
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

        // Real mode: call server
        $payload = [
            'license_key' => $key,
            'shop_domain' => request()->getHost(),
            'shop_name'   => optional(auth()->user()->shop)->name,
        ];

        $url = "{$this->serverUrl}/api/license/activate";

        Log::info('Sending activation request to license server', [
            'url' => $url,
            'payload' => [
                'license_key_tail' => substr($payload['license_key'], -6),
                'shop_domain' => $payload['shop_domain'],
                'shop_name' => $payload['shop_name'],
                'has_shop_name' => !empty($payload['shop_name']),
            ],
        ]);

        try {
            $response = Http::timeout(15)->post($url, $payload);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::info('License server response received', [
                'status_code' => $statusCode,
                'successful' => $response->successful(),
                'response_success_flag' => $responseData['success'] ?? null,
                'response_code' => $responseData['code'] ?? null,
                'response_message' => $responseData['message'] ?? null,
                'full_response' => $responseData,
            ]);

            if (!$response->successful() || !($responseData['success'] ?? false)) {
                Log::warning('License activation rejected by server', [
                    'key_tail' => substr($key, -6),
                    'status'   => $statusCode,
                    'code'     => $responseData['code'] ?? 'UNKNOWN',
                    'message'  => $responseData['message'] ?? 'No message',
                ]);

                return [
                    'success' => false,
                    'code'    => $responseData['code'] ?? 'SERVER_ERROR',
                    'message' => $responseData['message'] ?? 'Invalid license key or activation failed.',
                ];
            }

            // Server says it's valid – store locally
            Log::info('License activation successful, storing locally', [
                'key_tail' => substr($key, -6),
                'plan' => $responseData['plan'] ?? 'unknown',
                'expires_at' => $responseData['expires_at'] ?? 'unknown',
                'days_remaining' => $responseData['days_remaining'] ?? 'unknown',
            ]);

            $this->storeLicense($responseData, $key);

            return [
                'success'        => true,
                'plan'           => $responseData['plan'],
                'expires_at'     => $responseData['expires_at'],
                'days_remaining' => $responseData['days_remaining'],
            ];

        } catch (\Throwable $e) {
            Log::error("License activation network error", [
                'key_tail' => substr($key, -6),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'url' => $url,
                'exception_class' => get_class($e),
            ]);

            return [
                'success' => false,
                'code'    => 'NETWORK_ERROR',
                'message' => 'Could not reach the license server. Please check your internet connection.',
            ];
        }
    }

    /**
     * Check if the current license is valid.
     * No grace period – if expires_at is in the past, it's invalid.
     * Optionally re-verifies with server every X hours.
     */
    public function isValid(): bool
    {
        $license = $this->getLocalLicense();

        if (!$license) {
            Log::debug('isValid: No local license found');
            return false;
        }

        Log::debug('isValid: checking license', [
            'license_key_tail' => substr($license->license_key, -6),
            'status' => $license->status,
            'expires_at' => $license->expires_at?->toIso8601String(),
            'verified_at' => $license->verified_at?->toIso8601String(),
        ]);

        // Hard expiration check – no grace period
        if ($license->expires_at && $license->expires_at->isPast()) {
            Log::info('isValid: License expired (no grace period)', [
                'expires_at' => $license->expires_at->toIso8601String(),
            ]);
            return false;
        }

        // If status is not active, it's invalid
        if ($license->status !== 'active') {
            Log::info('isValid: License status is not active', ['status' => $license->status]);
            return false;
        }

        // Optional: re-verify with server periodically
        $needsServerCheck = !$license->verified_at
            || $license->verified_at->addHours(self::VERIFY_INTERVAL_HOURS)->isPast();

        if ($needsServerCheck) {
            Log::info('isValid: Periodic server re-verify needed', [
                'last_verified' => $license->verified_at?->toIso8601String(),
                'interval_hours' => self::VERIFY_INTERVAL_HOURS,
            ]);
            $this->refreshFromServer($license);
            $license->refresh();

            // After refresh, re-evaluate expiry and status
            if ($license->expires_at && $license->expires_at->isPast()) {
                Log::info('isValid: License expired after refresh');
                return false;
            }
            return $license->status === 'active';
        }

        Log::debug('isValid: License is valid (cached)');
        return true;
    }

    /**
     * Get the current license details for display.
     */
    public function details(): ?License
    {
        $license = $this->getLocalLicense();
        Log::debug('details: returning license', ['exists' => (bool)$license]);
        return $license;
    }

    /**
     * Get the status for the UI (used on the activation/license page).
     */
    public function status(): array
    {
        $license = $this->getLocalLicense();

        if (!$license) {
            Log::debug('status: No license');
            return ['status' => 'none', 'message' => 'No license activated.'];
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            Log::info('status: License expired', ['expires_at' => $license->expires_at]);
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
        $url = "{$this->serverUrl}/api/license/verify";

        Log::info('Refreshing license from server', [
            'license_key_tail' => substr($license->license_key, -6),
            'url' => $url,
            'shop_domain' => request()->getHost(),
        ]);

        try {
            $response = Http::timeout(10)
                ->post($url, [
                    'license_key' => $license->license_key,
                    'shop_domain' => request()->getHost(),
                ]);

            $data = $response->json();

            Log::info('Server verify response', [
                'status_code' => $response->status(),
                'valid' => $data['valid'] ?? null,
                'code' => $data['code'] ?? null,
                'message' => $data['message'] ?? null,
            ]);

            if ($response->successful() && ($data['valid'] ?? false)) {
                $license->update([
                    'status'              => 'active',
                    'days_remaining'      => $data['days_remaining'] ?? 0,
                    'expires_at'          => $data['expires_at'],
                    'verification_token'  => $data['token'] ?? null,
                    'verified_at'         => now(),
                ]);
                Log::info('License refreshed successfully (active)');
            } else {
                // Server says invalid – determine reason
                $code = $data['code'] ?? 'UNKNOWN';
                $status = match ($code) {
                    'EXPIRED'        => 'expired',
                    'KEY_REVOKED'    => 'revoked',
                    'KEY_SUSPENDED'  => 'suspended',
                    default          => 'invalid',
                };
                $license->update([
                    'status'      => $status,
                    'verified_at' => now(),
                ]);
                Log::warning('License refresh marked as invalid', ['new_status' => $status, 'code' => $code]);
            }
        } catch (\Throwable $e) {
            // Network error – do not change status, just log and update verified_at
            $license->update(['verified_at' => now()]);
            Log::warning("License server re-verify failed (network)", [
                'error_message' => $e->getMessage(),
                'license_key_tail' => substr($license->license_key, -6),
            ]);
        }
    }

    private function storeLicense(array $data, string $key): void
    {
        Log::info('Storing license locally', [
            'license_key_tail' => substr($key, -6),
            'plan' => $data['plan'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

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