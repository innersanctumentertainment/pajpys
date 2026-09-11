<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('account_type', 30);
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
            $table->char('currency', 3)->default('TTD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['account_type', 'is_active']);
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_account_id')->constrained()->restrictOnDelete();
            $table->string('entry_type', 30);
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('TTD');
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('idempotency_key')->unique();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['ledger_account_id', 'recorded_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('entry_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_accounts');
    }
};
