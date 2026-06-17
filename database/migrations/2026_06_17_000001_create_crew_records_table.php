<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crew_records', function (Blueprint $table) {
            $table->string('record_id')->primary();
            $table->string('depot')->index();
            $table->string('crew_id')->nullable()->index();
            $table->longText('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crew_records');
    }
};
