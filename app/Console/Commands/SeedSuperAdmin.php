<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedSuperAdmin extends Command
{
    protected $signature = 'crew:seed-superadmin';

    protected $description = 'Seed the configured superadmin account into admin_meta.';

    public function handle(): int
    {
        if (!Schema::hasTable('admin_meta')) {
            Schema::create('admin_meta', function (Blueprint $table) {
                $table->string('collection');
                $table->string('record_id');
                $table->json('payload');
                $table->timestamps();
                $table->primary(['collection', 'record_id']);
            });
        }

        $username = trim((string) env('SUPERADMIN_USERNAME', 'superadmin'));
        $password = (string) env('SUPERADMIN_PASSWORD', 'superadmin123');

        if ($username === '') {
            $this->error('SUPERADMIN_USERNAME is empty.');
            return self::FAILURE;
        }

        $exists = DB::table('admin_meta')
            ->where('collection', 'users')
            ->where('record_id', $username)
            ->exists();

        if ($exists) {
            $this->info('Superadmin already exists.');
            return self::SUCCESS;
        }

        DB::table('admin_meta')->insert([
            'collection' => 'users',
            'record_id' => $username,
            'payload' => json_encode([
                'username' => $username,
                'name' => 'Super Admin',
                'depot' => 'HQ',
                'role' => 'super_admin',
                'pw' => $password,
                'isHQ' => true,
                'isSuperAdmin' => true,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Superadmin seeded successfully.');

        return self::SUCCESS;
    }
}