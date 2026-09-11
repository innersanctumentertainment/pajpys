<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('va_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('country_code', 2)->default('TT');
            $table->string('timezone', 64)->default('America/Port_of_Spain');
            $table->string('avatar_path')->nullable();
            $table->decimal('hourly_rate_min', 19, 4)->nullable();
            $table->decimal('hourly_rate_max', 19, 4)->nullable();
            $table->char('currency', 3)->default('TTD');
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->string('availability_status', 30)->default('available');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('completed_jobs_count')->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'is_featured']);
            $table->index('availability_status');
            $table->index('average_rating');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('va_profiles');
    }
};
