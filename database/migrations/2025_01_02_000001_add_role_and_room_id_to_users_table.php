<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'attendant'])->default('attendant')->after('email');
            $table->foreignId('room_id')->nullable()->after('role')->constrained()->nullOnDelete();
            // admin  -> role='admin', room_id=null (sees everything)
            // attendant -> role='attendant', room_id=<their one room>
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
            $table->dropColumn('role');
        });
    }
};
