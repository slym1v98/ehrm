<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('period_code', 20)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('cutoff_date');
            $table->string('status', 20)->default('open');
            $table->foreignUuid('attendance_period_id')->nullable()->constrained('attendance_periods')->nullOnDelete();
            $table->uuid('workflow_request_id')->nullable();
            $table->foreignUuid('opened_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('opened_at');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignUuid('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('locked_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
