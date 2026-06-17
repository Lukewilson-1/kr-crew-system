<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crew_records', function (Blueprint $table) {
            if (!Schema::hasColumn('crew_records', 'staff_number')) {
                $table->string('staff_number')->nullable()->index()->after('crew_id');
            }
        });

        if (!Schema::hasTable('crew_shift_assignments')) {
            Schema::create('crew_shift_assignments', function (Blueprint $table) {
                $table->string('crew_record_id')->primary();
                $table->string('record_id')->index();
                $table->string('crew_id')->nullable()->index();
                $table->string('depot')->index();
                $table->string('shift')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('crew_records') && Schema::hasTable('crew_shift_assignments')) {
            $rows = DB::table('crew_records')->get();
            foreach ($rows as $row) {
                $payload = json_decode($row->payload ?? '{}', true);
                if (!is_array($payload)) {
                    $payload = [];
                }

                $shift = trim((string) ($payload['shift'] ?? ''));
                if ($shift !== '') {
                    DB::table('crew_shift_assignments')->updateOrInsert(
                        ['crew_record_id' => $row->record_id],
                        [
                            'record_id' => $row->record_id,
                            'crew_id' => (string) ($payload['id'] ?? $row->crew_id ?? $row->record_id),
                            'depot' => (string) ($payload['depot'] ?? $row->depot ?? 'HQ'),
                            'shift' => $shift,
                            'assigned_at' => $row->updated_at ?: now(),
                            'created_at' => $row->created_at ?: now(),
                            'updated_at' => now(),
                        ]
                    );
                    unset($payload['shift']);
                    DB::table('crew_records')
                        ->where('record_id', $row->record_id)
                        ->update([
                            'payload' => json_encode($payload),
                            'staff_number' => trim((string) ($payload['staff_number'] ?? $row->staff_number ?? '')) ?: null,
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crew_shift_assignments');
        Schema::table('crew_records', function (Blueprint $table) {
            if (Schema::hasColumn('crew_records', 'staff_number')) {
                $table->dropColumn('staff_number');
            }
        });
    }
};