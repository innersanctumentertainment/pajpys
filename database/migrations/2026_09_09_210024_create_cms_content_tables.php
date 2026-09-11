<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_content', function (Blueprint $table) {
            $table->id();
            $table->string('section_key', 100)->unique();
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->longText('body')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('category', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_title')->nullable();
            $table->string('author_company')->nullable();
            $table->text('content');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_featured', 'sort_order']);
        });

        Schema::create('featured_vas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('va_profile_id')->constrained('va_profiles')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('featured_from')->nullable();
            $table->timestamp('featured_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('va_profile_id');
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('favorite_vas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('va_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['client_id', 'va_id']);
            $table->index('va_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_vas');
        Schema::dropIfExists('featured_vas');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('homepage_content');
    }
};
