<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('notification_message_id');
            $table->string('channel', 20);
            $table->string('status', 20)->default('pending');
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->dateTime('available_at');
            $table->dateTime('locked_at')->nullable();
            $table->string('locked_by', 100)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('notification_message_id')
                ->references('id')
                ->on('notification_messages')
                ->cascadeOnDelete();

            $table->index(['status', 'attempts', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_outbox');
    }
};
