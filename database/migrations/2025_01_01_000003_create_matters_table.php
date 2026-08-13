<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('category', [
                'Maintenance', 'Cleanliness', 'Security',
                'Bedding & Supplies', 'Water/Power', 'Staffing', 'Other',
            ]);
            $table->text('description');
            $table->string('reported_by')->nullable();
            $table->enum('status', ['open', 'resolved'])->default('open');
            $table->date('resolved_date')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matters');
    }
};
