<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncOutbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    public function receive(Request $request)
    {
        $payload = $request->validate([
            'changes' => 'required|array',
            'changes.*.table_name' => 'required|string',
            'changes.*.record_id' => 'required|integer',
            'changes.*.action' => 'required|in:create,update,delete',
            'changes.*.data' => 'nullable|array',
        ]);

        request()->merge(['_sync' => true]);

        foreach ($payload['changes'] as $change) {
            DB::transaction(function () use ($change) {
                $table = $change['table_name'];
                $id    = $change['record_id'];
                $action = $change['action'];
                $data = $change['data'] ?? [];

                $modelClass = $this->getModelClass($table);
                if (!$modelClass) return;

                switch ($action) {
                    case 'create':
                    case 'update':
                        $modelClass::updateOrCreate(['id' => $id], $data);
                        break;
                    case 'delete':
                        if ($record = $modelClass::find($id)) $record->delete();
                        break;
                }
            });
        }

        return response()->json(['success' => true]);
    }

    public function export()
    {
        $changes = SyncOutbox::where('synced', false)
            ->orderBy('created_at')
            ->get()
            ->map(fn($o) => [
                'table_name' => $o->table_name,
                'record_id'  => $o->record_id,
                'action'     => $o->action,
                'data'       => $o->data,
            ]);

        SyncOutbox::where('synced', false)->update(['synced' => true]);

        return response()->json(['changes' => $changes]);
    }

    private function getModelClass($table)
    {
        $map = [
            'sales'     => \App\Models\Sale::class,
            'products'  => \App\Models\Product::class,
            // add other tables you want to sync
        ];
        return $map[$table] ?? null;
    }
}