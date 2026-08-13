<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();      // e.g. Nairobi, Longonot, Mtito Andei
            $table->unsignedInteger('beds');        // bed capacity
            $table->string('password')->nullable(); // attendant sign-in password (hashed on save)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
