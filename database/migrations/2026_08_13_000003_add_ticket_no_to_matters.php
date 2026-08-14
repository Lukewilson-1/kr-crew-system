<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->string('ticket_no', 32)->nullable()->after('id');
        });

        // Backfill existing matters so every ticket has a number and history
        // is immediately trackable. Format: MTR-<year>-<4-digit sequence>.
        $matters = DB::table('matters')->orderBy('id')->get(['id', 'date']);
        $seq = [];
        foreach ($matters as $matter) {
            $year = substr((string) $matter->date, 0, 4);
            $seq[$year] = ($seq[$year] ?? 0) + 1;
            DB::table('matters')->where('id', $matter->id)->update([
                'ticket_no' => 'MTR-'.$year.'-'.str_pad((string) $seq[$year], 4, '0', STR_PAD_LEFT),
            ]);
        }

        Schema::table('matters', function (Blueprint $table) {
            $table->unique('ticket_no');
        });
    }

    public function down(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->dropUnique('matters_ticket_no_unique');
            $table->dropColumn('ticket_no');
        });
    }
};
