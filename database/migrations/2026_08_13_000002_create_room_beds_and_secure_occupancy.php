<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-bed rows so individual beds can be managed (marked unusable) and
        // the check-in form can offer only beds that are free on the chosen date.
        Schema::create('room_beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('bed_no', 16);
            $table->boolean('is_usable')->default(true);
            $table->timestamps();

            $table->unique(['room_id', 'bed_no']);
        });

        DB::table('rooms')->orderBy('id')->get(['id', 'beds'])->each(function ($room) {
            for ($i = 1; $i <= $room->beds; $i++) {
                DB::table('room_beds')->insertOrIgnore([
                    'room_id' => $room->id,
                    'bed_no' => 'B'.$i,
                    'is_usable' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        // Resolve existing violations before the DB constraints land:
        // a crew member may only have one active ("in") check-in and a bed may
        // only be occupied by one active check-in. Keep the most recent arrival
        // and record the duplicates as same-day departures so history survives.
        $this->dedupeActive('staff_no', 'staff_no');
        $this->dedupeActive("CONCAT(room_id, '|', bed_no)", "CONCAT(room_id, '|', bed_no)");

        // MariaDB has no partial indexes, so enforce "one active check-in per
        // crew member / per bed" with STORED generated columns that are only
        // non-null while a record is active. NULLs are exempt from uniqueness.
        DB::statement(
            'ALTER TABLE attendance_records ADD COLUMN active_staff_no VARCHAR(64) '.
            "GENERATED ALWAYS AS (IF(status = 'in', staff_no, NULL)) STORED ".
            'AFTER status'
        );
        DB::statement(
            'ALTER TABLE attendance_records ADD COLUMN active_room_bed VARCHAR(80) '.
            "GENERATED ALWAYS AS (IF(status = 'in', CONCAT(room_id, ':', bed_no), NULL)) STORED ".
            'AFTER active_staff_no'
        );

        DB::statement('ALTER TABLE attendance_records ADD UNIQUE INDEX att_rec_active_staff_uniq (active_staff_no)');
        DB::statement('ALTER TABLE attendance_records ADD UNIQUE INDEX att_rec_active_bed_uniq (active_room_bed)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE attendance_records DROP INDEX att_rec_active_staff_uniq');
        DB::statement('ALTER TABLE attendance_records DROP INDEX att_rec_active_bed_uniq');

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['active_staff_no', 'active_room_bed']);
        });

        Schema::dropIfExists('room_beds');
    }

    protected function dedupeActive(string $groupBy, string $column): void
    {
        $dupes = DB::table('attendance_records')
            ->select(DB::raw($groupBy.' AS g'), DB::raw('COUNT(*) AS total'))
            ->where('status', 'in')
            ->groupBy(DB::raw($groupBy))
            ->having('total', '>', 1)
            ->get();

        foreach ($dupes as $dupe) {
            $keep = DB::table('attendance_records')
                ->whereRaw("{$column} = ?", [$dupe->g])
                ->where('status', 'in')
                ->orderByDesc('arrival_date')
                ->orderByDesc('id')
                ->value('id');

            if (! $keep) {
                continue;
            }

            DB::table('attendance_records')
                ->whereRaw("{$column} = ?", [$dupe->g])
                ->where('status', 'in')
                ->where('id', '!=', $keep)
                ->update([
                    'status' => 'out',
                    'departure_date' => DB::raw('arrival_date'),
                    'departure_time' => DB::raw('arrival_time'),
                ]);
        }
    }
};
