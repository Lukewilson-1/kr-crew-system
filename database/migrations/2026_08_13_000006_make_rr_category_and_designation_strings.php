<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Categories and designations are now stored in running_room_options and
        // may be changed at runtime, so the old hardcoded MySQL ENUM columns
        // must become plain strings to accept arbitrary values.
        DB::statement('ALTER TABLE matters MODIFY category VARCHAR(64) NOT NULL');
        DB::statement('ALTER TABLE attendance_records MODIFY designation VARCHAR(64) NOT NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE attendance_records MODIFY designation ENUM('Driver','Guard','Fireman','Inspector','Shunter','Other') NOT NULL");
        DB::statement("ALTER TABLE matters MODIFY category ENUM('Maintenance','Cleanliness','Security','Bedding & Supplies','Water/Power','Staffing','Other') NOT NULL");
    }
};
