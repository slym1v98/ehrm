<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shift_template_id')->constrained('shift_templates')->restrictOnDelete();
            $table->string('assignable_type', 20);
            $table->uuid('assignable_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->jsonb('recurrence_rule')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['assignable_type', 'assignable_id']);
            $table->index('shift_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_assignments');
    }
};
