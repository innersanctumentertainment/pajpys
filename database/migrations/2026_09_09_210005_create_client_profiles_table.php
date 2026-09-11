<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('country_code', 2)->default('TT');
            $table->string('timezone', 64)->default('America/Port_of_Spain');
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('billing_address_line1')->nullable();
            $table->string('billing_address_line2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_postal_code', 20)->nullable();
            $table->string('billing_country_code', 2)->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
