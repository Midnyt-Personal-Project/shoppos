<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Log};

class FileSync extends Command
{
    protected $signature = 'sync:file';
    protected $description = 'Sync via shared folder (file based)';

    public function handle()
    {
        $syncFolder = env('SYNC_FOLDER');
        if (!$syncFolder || !is_dir($syncFolder)) {
            $this->error("Sync folder not found: {$syncFolder}");
            Log::error("FileSync: folder missing");
            return 1;
        }

        $this->info("FileSync started. Watching: {$syncFolder}");
        Log::info("FileSync started. Folder: {$syncFolder}");

        $myHostname = gethostname();

        while (true) {
            $files = glob($syncFolder . DIRECTORY_SEPARATOR . '*.json');
            foreach ($files as $file) {
                // Skip files that are still being written (optional: wait a millisecond)
                clearstatcache();
                if (time() - filemtime($file) < 0.1) {
                    continue;
                }

                $content = file_get_contents($file);
                $payload = json_decode($content, true);
                if (!$payload) {
                    Log::warning("FileSync: invalid JSON in {$file}");
                    unlink($file);
                    continue;
                }

                // Skip files that were written by this PC
                if (($payload['source'] ?? '') === $myHostname) {
                    unlink($file);
                    continue;
                }

                // Apply the change
                $success = $this->applyChange($payload);

                if ($success) {
                    unlink($file);
                    Log::info("FileSync: applied change from {$payload['source']} - {$payload['action']} {$payload['table']} id {$payload['record_id']}");
                } else {
                    Log::error("FileSync: failed to apply change from {$payload['source']} - {$payload['action']} {$payload['table']} id {$payload['record_id']}");
                    // Optionally move to an error folder instead of deleting
                }
            }

            sleep(1);
        }
    }

    private function applyChange(array $payload): bool
    {
        $table = $payload['table'];
        $id    = $payload['record_id'];
        $action = $payload['action'];
        $data   = $payload['data'] ?? [];

        $modelClass = $this->getModelClass($table);
        if (!$modelClass) {
            Log::warning("FileSync: unknown table {$table}");
            return false;
        }

        // Mark that this operation is internal (prevents writing another file)
        request()->merge(['_sync' => true]);

        try {
            DB::transaction(function () use ($modelClass, $action, $id, $data) {
                switch ($action) {
                    case 'create':
                    case 'update':
                        $modelClass::updateOrCreate(['id' => $id], $data);
                        break;
                    case 'delete':
                        $record = $modelClass::find($id);
                        if ($record) $record->delete();
                        break;
                }
            });
            return true;
        } catch (\Exception $e) {
            Log::error("FileSync exception: " . $e->getMessage());
            return false;
        }
    }

    private function getModelClass(string $table): ?string
    {
        $map = [
            'products'  => \App\Models\Product::class,
            'sales'     => \App\Models\Sale::class,
            'customers' => \App\Models\Customer::class,
            // add other tables you want to sync
        ];
        return $map[$table] ?? null;
    }
}