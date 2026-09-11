<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_job_id')->constrained('marketplace_jobs')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('va_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->decimal('amount', 19, 4);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('TTD');
            $table->text('message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['marketplace_job_id', 'status']);
            $table->index(['va_id', 'status']);
        });
        Schema::create('offer_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->string('field_name', 50);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();
            $table->index(['offer_id', 'created_at']);
        });
        Schema::create('offer_negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposed_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('proposed_amount', 19, 4);
            $table->unsignedBigInteger('proposed_amount_minor');
            $table->char('currency', 3)->default('TTD');
            $table->text('message')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();
            $table->index(['offer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_negotiations');
        Schema::dropIfExists('offer_changes');
        Schema::dropIfExists('offers');
    }
};
