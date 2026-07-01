<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookup_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('lookup_groups')->cascadeOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['group_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookup_values');
    }
};
