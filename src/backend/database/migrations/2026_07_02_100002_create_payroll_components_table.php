<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('category', 30);
            $table->string('calculation_type', 30);
            $table->uuid('percent_base_component_id')->nullable();
            $table->decimal('default_amount', 15, 2)->nullable();
            $table->decimal('default_percent', 5, 2)->nullable();
            $table->boolean('taxable')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Self-referencing FK added after table creation
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->foreign('percent_base_component_id')
                ->references('id')
                ->on('payroll_components')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
