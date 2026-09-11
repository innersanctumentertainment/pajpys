<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->string('job_type', 30)->default('fixed');
            $table->decimal('default_budget_amount', 19, 4)->nullable();
            $table->unsignedBigInteger('default_budget_amount_minor')->nullable();
            $table->char('currency', 3)->default('TTD');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'is_public']);
        });
        Schema::create('job_template_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->unique(['job_template_id', 'skill_id']);
        });
        Schema::create('availability_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone', 64)->default('America/Port_of_Spain');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'day_of_week']);
        });
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_type', 30)->nullable();
            $table->string('external_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'is_public', 'sort_order']);
        });
    }
    public function down(): void { Schema::dropIfExists('portfolio_items'); Schema::dropIfExists('availability_schedules'); Schema::dropIfExists('job_template_skills'); Schema::dropIfExists('job_templates'); }
};
