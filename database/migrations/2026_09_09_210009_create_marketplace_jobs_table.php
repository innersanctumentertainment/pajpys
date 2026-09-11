<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->string('job_type', 30)->default('fixed');
            $table->string('status', 30)->default('draft');
            $table->string('visibility', 20)->default('public');
            $table->decimal('budget_amount', 19, 4)->nullable();
            $table->char('currency', 3)->default('TTD');
            $table->unsignedBigInteger('budget_amount_minor')->nullable();
            $table->decimal('hourly_rate', 19, 4)->nullable();
            $table->unsignedSmallInteger('estimated_hours')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['status', 'published_at']);
            $table->index('category_id');
        });

        Schema::create('job_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['marketplace_job_id', 'skill_id']);
        });

        Schema::create('job_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('marketplace_job_id');
        });

        Schema::create('job_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('va_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('applied');
            $table->text('cover_letter')->nullable();
            $table->decimal('proposed_amount', 19, 4)->nullable();
            $table->unsignedBigInteger('proposed_amount_minor')->nullable();
            $table->char('currency', 3)->default('TTD');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marketplace_job_id', 'va_id']);
            $table->index(['va_id', 'status']);
        });

        Schema::create('job_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('va_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marketplace_job_id', 'va_id']);
            $table->index(['va_id', 'status']);
        });

        Schema::create('job_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('va_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('active');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['marketplace_job_id', 'status']);
            $table->index(['va_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_assignments');
        Schema::dropIfExists('job_invitations');
        Schema::dropIfExists('job_candidates');
        Schema::dropIfExists('job_attachments');
        Schema::dropIfExists('job_skills');
        Schema::dropIfExists('marketplace_jobs');
    }
};
