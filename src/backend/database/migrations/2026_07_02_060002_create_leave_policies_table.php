<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->integer('max_consecutive_days')->nullable();
            $table->boolean('requires_attachment')->default(false);
            $table->integer('carry_over_limit')->nullable();
            $table->integer('carry_over_expiry_months')->nullable();
            $table->boolean('half_day_allowed')->default(true);
            $table->boolean('hourly_allowed')->default(false);
            $table->timestamps();

            $table->index(['leave_type_id', 'valid_from', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
