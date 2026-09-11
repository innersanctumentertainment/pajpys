<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->string('location')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('status', 30)->default('active');
            $table->boolean('is_verified')->default(false);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('completed_services_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });
        Schema::create('service_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('deliverables')->nullable();
            $table->string('service_type', 30)->default('general');
            $table->string('pricing_type', 20)->default('fixed');
            $table->decimal('price_amount', 19, 4)->nullable();
            $table->unsignedBigInteger('price_amount_minor')->nullable();
            $table->decimal('price_max_amount', 19, 4)->nullable();
            $table->unsignedBigInteger('price_max_amount_minor')->nullable();
            $table->char('currency', 3)->default('TTD');
            $table->unsignedSmallInteger('delivery_days')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_va_service')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('inquiry_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'published_at']);
            $table->index(['service_type', 'status']);
            $table->index('category_id');
        });
        Schema::create('service_listing_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['service_listing_id', 'skill_id']);
        });
        Schema::create('service_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->text('provider_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['provider_id', 'status']);
            $table->index(['client_id', 'status']);
        });
        Schema::table('marketplace_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('posting_fee_amount_minor')->nullable()->after('budget_amount_minor');
            $table->char('posting_fee_currency', 3)->nullable()->after('posting_fee_amount_minor');
            $table->timestamp('posting_fee_paid_at')->nullable()->after('posting_fee_currency');
            $table->foreignId('posting_fee_payment_id')->nullable()->after('posting_fee_paid_at')->constrained('payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('posting_fee_payment_id');
            $table->dropColumn(['posting_fee_amount_minor', 'posting_fee_currency', 'posting_fee_paid_at']);
        });
        Schema::dropIfExists('service_inquiries');
        Schema::dropIfExists('service_listing_skills');
        Schema::dropIfExists('service_listings');
        Schema::dropIfExists('provider_profiles');
    }
};
