<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->foreignUuid('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_at');
            $table->date('end_at');
            $table->string('duration_unit', 20);
            $table->integer('duration_minutes');
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->integer('balance_before')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'start_at', 'end_at']);
            $table->index(['employee_id', 'status']);
            $table->index(['leave_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
