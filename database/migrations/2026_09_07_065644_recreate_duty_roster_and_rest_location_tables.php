<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rest_locations')) {
            Schema::create('rest_locations', function (Blueprint $table) {
                $table->string('rest_location_code')->primary();
                $table->string('rest_location_name');
                $table->string('depot_code')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('duty_rosters')) {
            Schema::create('duty_rosters', function (Blueprint $table) {
                $table->string('roster_id')->primary();
                $table->string('depot_code')->index();
                $table->date('roster_date')->index();
                $table->string('period_label')->nullable()->index();
                $table->string('status')->default('draft')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('duty_roster_items')) {
            Schema::create('duty_roster_items', function (Blueprint $table) {
                $table->string('item_id')->primary();
                $table->string('roster_id')->index();
                $table->string('crew_record_id')->index();
                $table->string('crew_id')->nullable()->index();
                $table->string('shift_code')->nullable()->index();
                $table->string('train_type_code')->nullable()->index();
                $table->string('route_code')->nullable()->index();
                $table->string('rest_location_code')->nullable()->index();
                $table->date('duty_date')->index();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_roster_items');
        Schema::dropIfExists('duty_rosters');
        Schema::dropIfExists('rest_locations');
    }
};