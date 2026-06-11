<?php

namespace App\Http\Controllers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminMetaController extends Controller
{
    protected array $collections = ['depotMeta', 'designationMeta', 'statusMeta', 'reportMeta', 'users'];

    protected function ensureTable(): void
    {
        if (Schema::hasTable('admin_meta')) {
            return;
        }

        Schema::create('admin_meta', function (Blueprint $table) {
            $table->string('collection');
            $table->string('record_id');
            $table->json('payload');
            $table->timestamps();
            $table->primary(['collection', 'record_id']);
        });
    }

    public function index(Request $request)
    {
        $this->ensureTable();

        $items = DB::table('admin_meta')->get();
        $result = [];

        foreach ($items as $item) {
            $payload = json_decode($item->payload, true) ?: [];
            if (!isset($result[$item->collection])) {
                $result[$item->collection] = [];
            }
            $record = array_merge(['id' => $item->record_id], $payload);
            if ($item->collection === 'users' && !isset($record['username'])) {
                $record['username'] = $item->record_id;
            }
            $result[$item->collection][] = $record;
        }

        return response()->json($result);
    }

    public function save(Request $request, string $collection, string $id)
    {
        if (!in_array($collection, $this->collections, true)) {
            return response()->json(['error' => 'Invalid collection'], 400);
        }

        $this->ensureTable();

        $payload = $request->json()->all();
        if (!is_array($payload)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        if ($collection === 'users') {
            $payload['username'] = $id;
        }

        $record = ['collection' => $collection, 'record_id' => $id];
        $exists = DB::table('admin_meta')
            ->where('collection', $collection)
            ->where('record_id', $id)
            ->exists();

        if ($exists) {
            DB::table('admin_meta')
                ->where('collection', $collection)
                ->where('record_id', $id)
                ->update([
                    'payload' => json_encode($payload),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('admin_meta')->insert([
                'collection' => $collection,
                'record_id' => $id,
                'payload' => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['ok' => true, 'record' => array_merge(['id' => $id], $payload)]);
    }
}
