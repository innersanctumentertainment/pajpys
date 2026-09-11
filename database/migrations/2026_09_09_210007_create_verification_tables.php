<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'type', 'status']);
        });
        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_record_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['verification_record_id', 'document_type']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
        Schema::dropIfExists('verification_records');
    }
};
