<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('calendar_id')->constrained('holiday_calendars')->cascadeOnDelete();
            $table->date('date');
            $table->string('name');
            $table->string('type', 50)->default('public');
            $table->boolean('paid')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['calendar_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
