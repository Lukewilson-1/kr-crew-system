<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('builder_layout')->default('table');
            $table->json('builder_columns')->nullable();
            $table->json('builder_filters')->nullable();
            $table->string('builder_group_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['builder_layout', 'builder_columns', 'builder_filters', 'builder_group_by']);
        });
    }
};
