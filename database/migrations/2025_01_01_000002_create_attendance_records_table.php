<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('staff_no')->nullable();
            $table->enum('designation', ['Driver', 'Guard', 'Fireman', 'Inspector', 'Shunter', 'Other']);
            $table->string('bed_no')->nullable();
            $table->date('arrival_date');
            $table->time('arrival_time');
            $table->date('departure_date')->nullable();
            $table->time('departure_time')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['in', 'out'])->default('in');
            $table->timestamps();

            $table->index(['room_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
