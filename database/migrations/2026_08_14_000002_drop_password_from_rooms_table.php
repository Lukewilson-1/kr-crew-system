<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'password')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rooms') && ! Schema::hasColumn('rooms', 'password')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->string('password')->nullable();
            });
        }
    }
};
