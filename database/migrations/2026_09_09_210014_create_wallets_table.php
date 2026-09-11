<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('wallet_type', 30);
            $table->char('currency', 3)->default('TTD');
            $table->unsignedBigInteger('balance_minor')->default(0);
            $table->unsignedBigInteger('pending_balance_minor')->default(0);
            $table->unsignedBigInteger('locked_balance_minor')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'wallet_type', 'currency']);
            $table->index(['wallet_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
