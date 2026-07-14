<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'pw')) {
            DB::table('users')
                ->whereNotNull('pw')
                ->get()
                ->each(function ($user) {
                    $pw = (string) $user->pw;

                    if ($this->needsHash($pw)) {
                        DB::table('users')
                            ->where('username', $user->username)
                            ->update(['pw' => Hash::make($pw), 'updated_at' => now()]);
                    }
                });
        }

        if (Schema::hasTable('admin_meta') && Schema::hasColumn('admin_meta', 'payload')) {
            DB::table('admin_meta')
                ->where('collection', 'users')
                ->get()
                ->each(function ($row) {
                    $payload = json_decode($row->payload ?? '{}', true);

                    if (isset($payload['pw']) && $this->needsHash((string) $payload['pw'])) {
                        $payload['pw'] = Hash::make((string) $payload['pw']);

                        DB::table('admin_meta')
                            ->where('collection', 'users')
                            ->where('record_id', $row->record_id)
                            ->update([
                                'payload' => json_encode($payload),
                                'updated_at' => now(),
                            ]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Passwords are one-way hashed, so this migration cannot be rolled back.
    }

    private function needsHash(string $pw): bool
    {
        return $pw !== '' && !str_starts_with($pw, '$2y$') && !str_starts_with($pw, '$argon2i$') && !str_starts_with($pw, '$argon2id$');
    }
};
