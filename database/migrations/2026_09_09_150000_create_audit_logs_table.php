<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_username')->nullable()->index();
            $table->string('actor_ip')->nullable();
            $table->string('actor_user_agent', 512)->nullable();
            $table->string('event', 32)->index();
            $table->string('entity_type', 191)->index();
            $table->string('entity_id', 191)->index();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('changes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['actor_username', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};