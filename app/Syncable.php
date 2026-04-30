<?php

namespace App;

use App\Models\SyncOutbox;

trait Syncable
{
    protected static function bootSyncable()
    {
        static::created(function ($model) {
            self::addToOutbox($model, 'create');
        });
        static::updated(function ($model) {
            self::addToOutbox($model, 'update');
        });
        static::deleted(function ($model) {
            self::addToOutbox($model, 'delete');
        });
    }

    protected static function addToOutbox($model, $action)
    {
        // Avoid recursion when applying sync from another peer
        if (request()->has('_sync') && request()->input('_sync') === true) {
            return;
        }

        $data = ($action === 'delete') ? ['id' => $model->id] : $model->toArray();

        SyncOutbox::create([
            'table_name' => $model->getTable(),
            'record_id'  => $model->id,
            'action'     => $action,
            'data'       => $data,
            'synced'     => false,
        ]);
    }
}
