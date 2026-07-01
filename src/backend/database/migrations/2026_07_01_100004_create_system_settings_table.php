<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 150)->unique();
            $table->text('value')->nullable();
            $table->string('value_type', 30)->default('string');
            $table->string('group', 100)->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('editable')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
