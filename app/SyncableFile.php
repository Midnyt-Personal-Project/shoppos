<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait SyncableFile
{
    protected static function bootSyncableFile()
    {
        static::created(function ($model) {
            static::writeSyncFile($model, 'create');
        });
        static::updated(function ($model) {
            static::writeSyncFile($model, 'update');
        });
        static::deleted(function ($model) {
            static::writeSyncFile($model, 'delete');
        });
    }

    protected static function writeSyncFile($model, $action)
    {
        // Avoid loops when we are applying a sync from another file
        if (request()->has('_sync') && request()->input('_sync') === true) {
            return;
        }

        $syncFolder = env('SYNC_FOLDER');
        if (!$syncFolder || !is_dir($syncFolder)) {
            Log::warning("Sync folder not available: {$syncFolder}");
            return;
        }

        $data = ($action === 'delete') ? ['id' => $model->id] : $model->toArray();

        $payload = [
            'source'    => gethostname(),
            'table'     => $model->getTable(),
            'record_id' => $model->id,
            'action'    => $action,
            'data'      => $data,
            'timestamp' => microtime(true),
        ];

        $fileName = sprintf(
            '%s_%s_%d_%s.json',
            $action,
            $model->getTable(),
            $model->id,
            Str::random(8)
        );
        $filePath = $syncFolder . DIRECTORY_SEPARATOR . $fileName;

        file_put_contents($filePath, json_encode($payload, JSON_PRETTY_PRINT));
        Log::debug("Sync file written: {$filePath}");
    }
}