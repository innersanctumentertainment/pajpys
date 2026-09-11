<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('read_at');
        });
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 30);
            $table->string('notification_type', 100);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'channel', 'notification_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('notification_preferences'); Schema::dropIfExists('notifications'); }
};
