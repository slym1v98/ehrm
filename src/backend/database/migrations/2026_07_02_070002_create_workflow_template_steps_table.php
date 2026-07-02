<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_template_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_template_id')->constrained('workflow_templates')->cascadeOnDelete();
            $table->integer('step_order');
            $table->string('name');
            $table->string('assignee_type', 30);
            $table->uuid('assignee_id')->nullable();
            $table->jsonb('condition')->nullable();
            $table->timestamps();

            $table->unique(['workflow_template_id', 'step_order']);
            $table->index(['workflow_template_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_template_steps');
    }
};
