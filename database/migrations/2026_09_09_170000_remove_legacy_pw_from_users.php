<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'pw')) {
            // Preserve logins during the transition: move any legacy hash that
            // lives only in `pw` into the canonical `password` column first.
            if (Schema::hasColumn('users', 'password')) {
                DB::table('users')
                    ->where(fn ($q) => $q->whereNull('password')->orWhere('password', ''))
                    ->whereNotNull('pw')
                    ->where('pw', '!=', '')
                    ->get(['username', 'pw'])
                    ->each(function ($user): void {
                        DB::table('users')
                            ->where('username', $user->username)
                            ->update([
                                'password' => $user->pw,
                                'updated_at' => now(),
                            ]);
                    });
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('pw');
            });
        }

        // Defensive: strip any leftover `pw` keys from admin_meta user payloads.
        if (Schema::hasTable('admin_meta') && Schema::hasColumn('admin_meta', 'payload')) {
            DB::table('admin_meta')
                ->where('collection', 'users')
                ->get(['record_id', 'payload'])
                ->each(function ($row): void {
                    $payload = json_decode($row->payload ?? '{}', true);
                    if (! is_array($payload) || ! array_key_exists('pw', $payload)) {
                        return;
                    }
                    unset($payload['pw']);
                    DB::table('admin_meta')
                        ->where('collection', 'users')
                        ->where('record_id', $row->record_id)
                        ->update([
                            'payload' => json_encode($payload),
                            'updated_at' => now(),
                        ]);
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'pw')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pw')->nullable()->after('password');
            });
        }
    }
};