<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('entry_id')->constrained('payroll_entries')->cascadeOnDelete();
            $table->foreignUuid('component_id')->constrained('payroll_components')->restrictOnDelete();
            $table->string('category', 30);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('calculation_note', 255)->nullable();
            $table->timestamps();

            $table->index('entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entry_lines');
    }
};
