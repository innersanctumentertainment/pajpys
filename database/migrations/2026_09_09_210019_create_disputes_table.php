<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('raised_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('against_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('open');
            $table->string('reason_code', 50);
            $table->text('description');
            $table->decimal('disputed_amount', 19, 4)->nullable();
            $table->unsignedBigInteger('disputed_amount_minor')->nullable();
            $table->char('currency', 3)->default('TTD');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['marketplace_job_id', 'status']);
            $table->index(['raised_by', 'status']);
        });
        Schema::create('dispute_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('dispute_id');
        });
    }
    public function down(): void { Schema::dropIfExists('dispute_evidence'); Schema::dropIfExists('disputes'); }
};
