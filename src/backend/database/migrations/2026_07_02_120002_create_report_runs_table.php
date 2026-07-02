<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_definition_id');
            $table->uuid('requested_by');
            $table->jsonb('filters')->default('{}');
            $table->string('status', 20)->default('requested');
            $table->jsonb('result')->nullable();
            $table->text('error')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('report_definition_id')->references('id')->on('report_definitions')->cascadeOnDelete();
            $table->index('requested_by');
            $table->index('status');
            $table->index(['report_definition_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
    }
};
