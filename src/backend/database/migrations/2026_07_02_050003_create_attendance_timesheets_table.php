<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_timesheets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attendance_period_id');
            $table->uuid('employee_id');
            $table->date('work_date');
            $table->uuid('shift_assignment_id')->nullable();
            $table->integer('expected_minutes')->default(0);
            $table->integer('worked_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_leave_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->string('result_status', 20);
            $table->string('calculation_run_id', 50)->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date', 'attendance_period_id'], 'uniq_timesheet_employee_date_period');
            $table->index('attendance_period_id', 'idx_timesheets_period');
            $table->index(['employee_id', 'work_date'], 'idx_timesheets_employee_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_timesheets');
    }
};
