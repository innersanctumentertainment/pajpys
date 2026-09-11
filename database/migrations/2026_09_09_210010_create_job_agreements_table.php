<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('job_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('va_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('draft');
            $table->decimal('agreed_amount', 19, 4);
            $table->unsignedBigInteger('agreed_amount_minor');
            $table->char('currency', 3)->default('TTD');
            $table->text('terms')->nullable();
            $table->timestamp('client_signed_at')->nullable();
            $table->timestamp('va_signed_at')->nullable();
            $table->timestamp('effective_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['marketplace_job_id', 'status']);
        });

        Schema::create('job_agreement_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_agreement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('confirmed_at');
            $table->timestamps();

            $table->unique(['job_agreement_id', 'user_id']);
        });

        Schema::create('job_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('job_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['marketplace_job_id', 'status']);
        });

        Schema::create('job_completion_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('job_deliverable_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('marketplace_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_completion_evidence');
        Schema::dropIfExists('job_deliverables');
        Schema::dropIfExists('job_agreement_confirmations');
        Schema::dropIfExists('job_agreements');
    }
};
