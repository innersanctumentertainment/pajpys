<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_system')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['marketplace_job_id', 'created_at']);
            $table->index(['recipient_id', 'read_at']);
        });
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_message_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('job_message_id');
        });
        Schema::create('job_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 50);
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['marketplace_job_id', 'occurred_at']);
            $table->index('event_type');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('job_activity');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('job_messages');
    }
};
