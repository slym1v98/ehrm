<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('channel', 20);
            $table->string('template_code', 100)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->unique(['user_id', 'channel', 'template_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
