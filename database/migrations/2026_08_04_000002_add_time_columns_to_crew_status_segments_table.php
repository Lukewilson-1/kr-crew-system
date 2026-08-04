<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crew_status_segments', function (Blueprint $table) {
            if (!Schema::hasColumn('crew_status_segments', 'date')) {
                $table->date('date')->nullable()->after('day');
            }
            if (!Schema::hasColumn('crew_status_segments', 'status')) {
                $table->string('status')->nullable()->after('status_code');
            }
            if (!Schema::hasColumn('crew_status_segments', 'start_time')) {
                $table->string('start_time')->nullable()->after('status');
            }
            if (!Schema::hasColumn('crew_status_segments', 'end_time')) {
                $table->string('end_time')->nullable()->after('start_time');
            }
            if (!Schema::hasColumn('crew_status_segments', 'note')) {
                $table->text('note')->nullable()->after('end_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crew_status_segments', function (Blueprint $table) {
            $table->dropColumn(['date', 'status', 'start_time', 'end_time', 'note']);
        });
    }
};
