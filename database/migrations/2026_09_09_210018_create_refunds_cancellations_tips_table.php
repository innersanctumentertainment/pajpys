<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('marketplace_job_id')->nullable()->constrained('marketplace_jobs')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->decimal('amount', 19, 4);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('TTD');
            $table->text('reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['payment_id', 'status']);
        });
        Schema::create('cancellation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('job_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cancelled_by')->constrained('users')->cascadeOnDelete();
            $table->string('cancelled_by_role', 20);
            $table->string('reason_code', 50)->nullable();
            $table->text('reason_detail')->nullable();
            $table->decimal('fee_amount', 19, 4)->default(0);
            $table->unsignedBigInteger('fee_amount_minor')->default(0);
            $table->char('currency', 3)->default('TTD');
            $table->timestamp('cancelled_at');
            $table->timestamps();
            $table->index(['marketplace_job_id', 'cancelled_at']);
        });
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->nullable()->constrained('marketplace_jobs')->nullOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 19, 4);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('TTD');
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['to_user_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('tips'); Schema::dropIfExists('cancellation_records'); Schema::dropIfExists('refunds'); }
};
