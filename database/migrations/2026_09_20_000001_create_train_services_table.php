<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_services', function (Blueprint $table) {
            $table->string('service_id')->primary();
            $table->string('depot_code')->index();
            $table->string('route_code')->nullable()->index();
            $table->string('train_type', 32)->default('commuter');
            $table->unsignedTinyInteger('departure_day')->comment('1=Monday ... 7=Sunday');
            $table->string('departure_time', 5);
            $table->unsignedTinyInteger('frequency_weeks')->default(1);
            $table->unsignedTinyInteger('lead_days_advance')->default(7)->comment('How far ahead pending bookings are created');
            $table->string('crew_key', 32)->nullable()->comment('staff/record hint: depot route, crew key, or booked crew id');
            $table->text('crew_hints')->nullable()->comment('Extra staff_no / record_id hints, one per line');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_services');
    }
};
