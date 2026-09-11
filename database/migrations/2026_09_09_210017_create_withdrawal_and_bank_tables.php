<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('account_holder_name');
            $table->string('bank_name');
            $table->text('account_number_encrypted');
            $table->text('routing_number_encrypted')->nullable();
            $table->text('swift_code_encrypted')->nullable();
            $table->string('account_type', 30)->nullable();
            $table->char('currency', 3)->default('TTD');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'is_default']);
        });
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('bank_detail_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->decimal('amount', 19, 4);
            $table->unsignedBigInteger('amount_minor');
            $table->decimal('fee_amount', 19, 4)->default(0);
            $table->unsignedBigInteger('fee_amount_minor')->default(0);
            $table->decimal('net_amount', 19, 4);
            $table->unsignedBigInteger('net_amount_minor');
            $table->char('currency', 3)->default('TTD');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('bank_details');
    }
};
