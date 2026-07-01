<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('permission_code', 150);
            $table->foreign('permission_code')->references('code')->on('permissions')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['role_id', 'permission_code']);
            $table->index('permission_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
