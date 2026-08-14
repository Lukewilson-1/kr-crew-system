<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('report_type')->nullable()->after('type');
        });

        // Backfill from the configured route so existing reports keep working
        // without manual edits. New reports choose report_type in the admin form.
        $mapping = [
            'reports.daily-status' => 'status',
            'reports.monthly-register' => 'monthly',
            'reports.utilization' => 'utilization',
            'reports.absence' => 'absence',
            'reports.printable' => 'print',
        ];

        foreach ($mapping as $route => $reportType) {
            DB::table('reports')
                ->where('route_name', $route)
                ->whereNull('report_type')
                ->update(['report_type' => $reportType]);
        }
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('report_type');
        });
    }
};
