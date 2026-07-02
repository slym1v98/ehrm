<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_raw_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('source', 20);
            $table->string('event_type', 20);
            $table->timestampTz('event_time');
            $table->jsonb('geo_point')->nullable();
            $table->jsonb('payload')->default('{}');
            $table->timestamp('created_at', 0)->nullable();
            $table->index(['employee_id', 'event_time']);
            $table->index(['source', 'event_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_raw_logs');
    }
};
