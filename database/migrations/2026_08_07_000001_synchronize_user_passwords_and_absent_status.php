<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('status_codes')) {
            DB::table('status_codes')->updateOrInsert(
                ['status_code' => 'ABS'],
                [
                    'status_label' => 'Absent',
                    'sort_order' => 550,
                    'is_terminal' => false,
                    'metadata' => json_encode(['bg' => '#FEF2F2', 'fg' => '#991B1B', 'active' => true]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'password') && Schema::hasColumn('users', 'pw')) {
            DB::table('users')
                ->get()
                ->each(function ($user): void {
                    $password = (string) ($user->password ?? '');
                    $legacyPassword = (string) $user->pw;

                    if ($password === '' && $legacyPassword === '') {
                        return;
                    }

                    $passwordHash = $password !== ''
                        ? $this->normalizePassword($password)
                        : $this->normalizePassword($legacyPassword);

                    DB::table('users')
                        ->where('username', $user->username)
                        ->update([
                            'password' => $passwordHash,
                            'pw' => $passwordHash,
                            'updated_at' => now(),
                        ]);
                });
        }

        if (Schema::hasTable('admin_meta') && Schema::hasColumn('admin_meta', 'payload')) {
            DB::table('admin_meta')
                ->where('collection', 'users')
                ->get()
                ->each(function ($row): void {
                    $payload = json_decode($row->payload ?? '{}', true);

                    if (!is_array($payload)) {
                        return;
                    }

                    $password = (string) ($payload['password'] ?? '');
                    $legacyPassword = (string) ($payload['pw'] ?? '');

                    if ($password === '' && $legacyPassword !== '') {
                        $payload['password'] = $this->normalizePassword($legacyPassword);
                    } elseif ($password !== '') {
                        $payload['password'] = $this->normalizePassword($password);
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
        if (Schema::hasTable('status_codes')) {
            DB::table('status_codes')->where('status_code', 'ABS')->delete();
        }
    }

    private function normalizePassword(string $password): string
    {
        if ($password === '') {
            return '';
        }

        if (str_starts_with($password, '$2y$') || str_starts_with($password, '$argon2i$') || str_starts_with($password, '$argon2id$')) {
            return $password;
        }

        return Hash::make($password);
    }
};
