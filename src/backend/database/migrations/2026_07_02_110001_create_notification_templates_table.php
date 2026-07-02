<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->string('channel', 20);
            $table->string('subject', 500)->nullable();
            $table->text('body');
            $table->jsonb('variables')->default('[]');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('channel');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
