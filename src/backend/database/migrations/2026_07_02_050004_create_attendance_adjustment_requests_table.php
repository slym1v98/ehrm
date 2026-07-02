<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_adjustment_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attendance_timesheet_id');
            $table->uuid('employee_id');
            $table->uuid('requested_by');
            $table->text('reason');
            $table->string('evidence_file', 500)->nullable();
            $table->jsonb('corrections');
            $table->string('status', 20)->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestamps();
            $table->index('attendance_timesheet_id', 'idx_adj_req_timesheet');
            $table->index('status', 'idx_adj_req_status');
            $table->unique('attendance_timesheet_id', 'uniq_adj_req_pending')->where('status = \'pending\'');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustment_requests');
    }
};
