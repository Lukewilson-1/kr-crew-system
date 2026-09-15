<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the view-only 'viewer' (Control Desk) role for existing installs.
     * The role carries no role_permissions, so viewer accounts cannot
     * reach the admin console and are rejected from all write routes.
     */
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')->updateOrInsert(
                ['role_code' => 'viewer'],
                [
                    'role_name' => 'Viewer (Control Desk)',
                    'description' => 'View-only access to crew status and reports. Cannot edit data or use the admin console.',
                    'is_system' => true,
                    'metadata' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')->where('role_code', 'viewer')->delete();
        }
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('role_code', 'viewer')->delete();
        }
    }
};