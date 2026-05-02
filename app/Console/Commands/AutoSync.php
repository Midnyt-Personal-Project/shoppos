<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache, Http, Log};
use App\Models\{Peer, SyncOutbox};

class AutoSync extends Command
{
    protected $signature = 'sync:auto';
    protected $description = 'Sync with peers every second';

    private function getLocalIpAddress(): ?string
    {
        // Try common methods
        $hostname = gethostname();
        $ip = gethostbyname($hostname);
        if ($ip && $ip !== '127.0.0.1' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            Log::info("AutoSync: Detected IP via gethostbyname: {$ip}");
            return $ip;
        }

        // Use net_get_interfaces if available
        if (function_exists('net_get_interfaces')) {
            $interfaces = net_get_interfaces();
            foreach ($interfaces as $interface) {
                if ($interface['up'] && !$interface['loopback'] && isset($interface['unicast'][0]['address'])) {
                    $addr = $interface['unicast'][0]['address'];
                    if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        Log::info("AutoSync: Detected IP via net_get_interfaces: {$addr}");
                        return $addr;
                    }
                }
            }
        }

        // Windows ipconfig fallback
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('ipconfig', $output);
            foreach ($output as $line) {
                if (preg_match('/IPv4 Address[ .]+: ([0-9.]+)/', $line, $matches)) {
                    $addr = $matches[1];
                    if ($addr !== '127.0.0.1') {
                        Log::info("AutoSync: Detected IP via ipconfig: {$addr}");
                        return $addr;
                    }
                }
            }
        }

        Log::error('AutoSync: Could not detect local IP address.');
        return null;
    }

    public function handle()
    {
        $localIp = $this->getLocalIpAddress();
        if (!$localIp) {
            $this->error('Could not detect local IP. Please set APP_LOCAL_IP in .env manually.');
            Log::critical('AutoSync: No local IP found, exiting.');
            return 1;
        }
        Cache::forever('local_ip', $localIp);
        $this->info("Sync started. Local IP: {$localIp}");
        Log::info("AutoSync started. Local IP: {$localIp}");

        while (true) {
            $peers = Peer::where('is_active', true)->get();
            Log::debug('AutoSync: Checking peers', ['count' => $peers->count()]);

            foreach ($peers as $peer) {
                if ($peer->ip_address === $localIp) {
                    Log::debug("AutoSync: Skipping self ({$localIp})");
                    continue;
                }

                Log::debug("AutoSync: Processing peer {$peer->ip_address}");

                // Ping check
                $pingCmd = "ping -n 1 -w 500 " . escapeshellarg($peer->ip_address);
                exec($pingCmd, $out, $code);
                if ($code !== 0) {
                    Log::warning("AutoSync: Peer {$peer->ip_address} is NOT reachable (ping failed)");
                    continue;
                }
                Log::debug("AutoSync: Peer {$peer->ip_address} is reachable");

                $peer->update(['last_seen' => now()]);

                // --- PUSH unsynced changes to peer ---
                $unsynced = SyncOutbox::where('synced', false)->get();
                $unsyncedCount = $unsynced->count();
                Log::debug("AutoSync: Found {$unsyncedCount} unsynced records to push to {$peer->ip_address}");

                if ($unsyncedCount > 0) {
                    $payload = $unsynced->map(fn($i) => [
                        'table_name' => $i->table_name,
                        'record_id'  => $i->record_id,
                        'action'     => $i->action,
                        'data'       => $i->data,
                    ])->toArray();

                    try {
                        $pushUrl = "http://{$peer->ip_address}:8000/api/sync/receive";
                        Log::debug("AutoSync: POST to {$pushUrl}");
                        $res = Http::timeout(1)->post($pushUrl, ['changes' => $payload]);
                        if ($res->successful()) {
                            $updated = SyncOutbox::where('synced', false)->update(['synced' => true]);
                            Log::info("AutoSync: Successfully pushed {$unsyncedCount} records to {$peer->ip_address}, marked {$updated} as synced.");
                        } else {
                            Log::warning("AutoSync: Push to {$peer->ip_address} failed with status {$res->status()}, response: " . substr($res->body(), 0, 200));
                        }
                    } catch (\Exception $e) {
                        Log::error("AutoSync: Push exception for {$peer->ip_address}: " . $e->getMessage());
                    }
                } else {
                    Log::debug("AutoSync: No unsynced records to push to {$peer->ip_address}");
                }

                // --- PULL changes from peer ---
                try {
                    $pullUrl = "http://{$peer->ip_address}:8000/api/sync/export";
                    Log::debug("AutoSync: GET from {$pullUrl}");
                    $res = Http::timeout(1)->get($pullUrl);
                    if ($res->successful()) {
                        $changes = $res->json('changes');
                        if (count($changes) > 0) {
                            Log::info("AutoSync: Pulled " . count($changes) . " changes from {$peer->ip_address}, applying locally.");
                            // Apply to local DB via our own receive endpoint (with _sync flag)
                            $applyRes = Http::timeout(2)->post("http://{$localIp}:8000/api/sync/receive", ['changes' => $changes]);
                            if ($applyRes->successful()) {
                                Log::info("AutoSync: Successfully applied pulled changes from {$peer->ip_address}");
                            } else {
                                Log::warning("AutoSync: Failed to apply pulled changes from {$peer->ip_address}, status: " . $applyRes->status());
                            }
                        } else {
                            Log::debug("AutoSync: No changes to pull from {$peer->ip_address}");
                        }
                    } else {
                        Log::warning("AutoSync: Pull from {$peer->ip_address} failed with status {$res->status()}, response: " . substr($res->body(), 0, 200));
                    }
                } catch (\Exception $e) {
                    Log::error("AutoSync: Pull exception for {$peer->ip_address}: " . $e->getMessage());
                }
            }

            sleep(1);
        }
    }
}