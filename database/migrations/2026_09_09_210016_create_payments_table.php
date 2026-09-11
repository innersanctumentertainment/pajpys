<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('marketplace_job_id')->nullable()->constrained('marketplace_jobs')->nullOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_type', 30);
            $table->string('status', 30)->default('pending');
            $table->decimal('amount', 19, 4);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('TTD');
            $table->decimal('fee_amount', 19, 4)->default(0);
            $table->unsignedBigInteger('fee_amount_minor')->default(0);
            $table->decimal('net_amount', 19, 4);
            $table->unsignedBigInteger('net_amount_minor');
            $table->string('gateway', 50)->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['payer_id', 'status']);
            $table->index(['marketplace_job_id', 'payment_type']);
            $table->index('gateway_reference');
        });
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number')->default(1);
            $table->string('status', 30)->default('pending');
            $table->string('gateway', 50)->nullable();
            $table->string('gateway_reference')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();
            $table->index(['payment_id', 'attempt_number']);
            $table->index('status');
        });
        Schema::create('payment_gateway_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 50);
            $table->string('event_type', 100);
            $table->string('gateway_event_id')->nullable();
            $table->json('payload');
            $table->string('processing_status', 30)->default('received');
            $table->timestamp('received_at');
            $table->timestamps();
            $table->unique(['gateway', 'gateway_event_id']);
            $table->index(['payment_id', 'event_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('payment_gateway_events'); Schema::dropIfExists('payment_attempts'); Schema::dropIfExists('payments'); }
};
