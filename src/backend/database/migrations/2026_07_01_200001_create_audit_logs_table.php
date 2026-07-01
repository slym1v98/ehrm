<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('module', 100);
            $table->string('entity_type', 100);
            $table->string('entity_id')->nullable();
            $table->jsonb('before_payload')->nullable();
            $table->jsonb('after_payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('result', 30)->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['module', 'entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
