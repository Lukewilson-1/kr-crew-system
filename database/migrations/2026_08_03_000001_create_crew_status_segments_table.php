<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crew_status_segments')) {
            Schema::create('crew_status_segments', function (Blueprint $table) {
                $table->string('segment_id')->primary();
                $table->string('crew_record_id')->index();
                $table->string('crew_id')->nullable()->index();
                $table->string('depot_code')->nullable()->index();
                $table->string('month_key')->nullable()->index();
                $table->unsignedTinyInteger('day')->index();
                $table->unsignedSmallInteger('sort_order')->default(100)->index();
                $table->string('status_code')->index();
                $table->string('train_type')->nullable();
                $table->string('route')->nullable();
                $table->string('book_time')->nullable();
                $table->timestamp('rest_started_at')->nullable();
                $table->string('away_depot')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crew_status_segments');
    }
};
