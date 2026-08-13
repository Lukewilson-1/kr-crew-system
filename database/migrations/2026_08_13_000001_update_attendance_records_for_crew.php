<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Designation now comes from the crew member record (e.g. LD, TA, RSF)
        // instead of a fixed enum, so loosen the column.
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('designation', 100)->change();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->index(['room_id', 'status', 'arrival_date'], 'att_rec_room_status_arrival_idx');
            $table->index('staff_no', 'att_rec_staff_no_idx');
        });

        Schema::table('matters', function (Blueprint $table) {
            $table->index(['room_id', 'date'], 'matters_room_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('att_rec_room_status_arrival_idx');
            $table->dropIndex('att_rec_staff_no_idx');
        });

        Schema::table('matters', function (Blueprint $table) {
            $table->dropIndex('matters_room_date_idx');
        });

        // Restoring the original enum is intentionally skipped to avoid
        // truncating data; downgrade should be handled manually.
    }
};
