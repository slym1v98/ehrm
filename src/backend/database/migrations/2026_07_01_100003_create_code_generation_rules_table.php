<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_generation_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type', 100)->unique();
            $table->string('prefix', 50);
            $table->string('pattern', 100);
            $table->integer('sequence_padding')->default(5);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_generation_rules');
    }
};
