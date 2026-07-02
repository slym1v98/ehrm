<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->string('run_type', 20)->default('initial');
            $table->string('status', 20)->default('running');
            $table->string('formula_version', 50);
            $table->foreignUuid('triggered_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->text('error_summary')->nullable();
            $table->timestamps();

            $table->index(['period_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
