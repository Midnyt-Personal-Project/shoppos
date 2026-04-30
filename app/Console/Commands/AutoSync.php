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
            return $ip;
        }

        // Use net_get_interfaces if available
        if (function_exists('net_get_interfaces')) {
            $interfaces = net_get_interfaces();
            foreach ($interfaces as $interface) {
                if ($interface['up'] && !$interface['loopback'] && isset($interface['unicast'][0]['address'])) {
                    $addr = $interface['unicast'][0]['address'];
                    if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
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
                    if ($addr !== '127.0.0.1') return $addr;
                }
            }
        }

        return null;
    }

    public function handle()
    {
        $localIp = $this->getLocalIpAddress();
        if (!$localIp) {
            $this->error('Could not detect local IP. Please set APP_LOCAL_IP in .env manually.');
            return 1;
        }
        Cache::forever('local_ip', $localIp);
        $this->info("Sync started. Local IP: {$localIp}");

        while (true) {
            $peers = Peer::where('is_active', true)->get();

            foreach ($peers as $peer) {
                if ($peer->ip_address === $localIp) continue;

                // Ping check
                $ping = exec("ping -n 1 -w 500 " . escapeshellarg($peer->ip_address), $out, $code);
                if ($code !== 0) continue;

                $peer->update(['last_seen' => now()]);

                // Push
                $unsynced = SyncOutbox::where('synced', false)->get();
                if ($unsynced->count()) {
                    $payload = $unsynced->map(fn($i) => [
                        'table_name' => $i->table_name,
                        'record_id'  => $i->record_id,
                        'action'     => $i->action,
                        'data'       => $i->data,
                    ])->toArray();

                    try {
                        $res = Http::timeout(1)->post("http://{$peer->ip_address}:8000/api/sync/receive", ['changes' => $payload]);
                        if ($res->successful()) {
                            SyncOutbox::where('synced', false)->update(['synced' => true]);
                        }
                    } catch (\Exception $e) {
                        Log::warning("Push to {$peer->ip_address} failed: " . $e->getMessage());
                    }
                }

                // Pull
                try {
                    $res = Http::timeout(1)->get("http://{$peer->ip_address}:8000/api/sync/export");
                    if ($res->successful() && count($res->json('changes'))) {
                        Http::timeout(2)->post("http://{$localIp}:8000/api/sync/receive", ['changes' => $res->json('changes')]);
                    }
                } catch (\Exception $e) {
                    Log::warning("Pull from {$peer->ip_address} failed: " . $e->getMessage());
                }
            }

            sleep(1);
        }
    }
}