<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_template_id')->constrained('workflow_templates')->cascadeOnDelete();
            $table->string('subject_type', 80);
            $table->uuid('subject_id');
            $table->string('status', 30)->default('pending');
            $table->integer('current_step')->nullable();
            $table->uuid('submitted_by');
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('status');
            $table->index(['submitted_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_requests');
    }
};
