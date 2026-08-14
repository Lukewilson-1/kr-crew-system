<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms')) {
            return;
        }

        if (! Schema::hasColumn('rooms', 'depot_code')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->string('depot_code', 20)->nullable()->index()->after('name');
            });
        }

        $mapping = [
            'Nairobi' => 'MKR',
            'Longonot' => 'MKR',
            'Mtito Andei' => 'MTO',
            'Nakuru' => 'NRO',
            'Nanyuki' => 'NUK',
            'Malaba' => 'MLB',
            'Eldoret' => 'ELD',
            'Kisumu' => 'KSM',
        ];

        foreach ($mapping as $name => $depot) {
            DB::table('rooms')->where('name', $name)->update(['depot_code' => $depot]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'depot_code')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropIndex(['depot_code']);
                $table->dropColumn('depot_code');
            });
        }
    }
};
