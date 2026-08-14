<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matter_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('matter_id');
            $table->string('filename', 255);
            $table->timestamps();

            $table->foreign('matter_id')
                ->references('id')
                ->on('matters')
                ->onDelete('cascade');

            $table->index('matter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matter_photos');
    }
};
