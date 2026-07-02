<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('template_code', 100);
            $table->string('channel', 20);
            $table->uuid('recipient_user_id');
            $table->string('recipient_address', 255)->nullable();
            $table->string('subject_rendered', 500)->nullable();
            $table->text('body_rendered');
            $table->jsonb('payload')->default('{}');
            $table->string('status', 20)->default('pending');
            $table->string('priority', 20)->default('normal');
            $table->text('error')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index('recipient_user_id');
            $table->index(['status', 'priority']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_messages');
    }
};
