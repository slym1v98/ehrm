<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_request_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_request_id')->constrained('workflow_requests')->cascadeOnDelete();
            $table->integer('step_order');
            $table->string('action', 30);
            $table->uuid('actor_id');
            $table->text('comment')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamp('created_at');

            $table->index(['workflow_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_request_actions');
    }
};
