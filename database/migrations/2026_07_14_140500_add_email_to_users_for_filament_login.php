<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable()->unique()->after('username');
            });
        }

        DB::table('users')
            ->whereNull('email')
            ->orderBy('username')
            ->get(['username'])
            ->each(function (object $user): void {
                $username = strtolower(trim((string) $user->username));

                if ($username === '') {
                    return;
                }

                DB::table('users')
                    ->where('username', $user->username)
                    ->update([
                        'email' => $username . '@localhost.test',
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn('email');
        });
    }
};
