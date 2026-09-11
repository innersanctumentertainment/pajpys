<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_verified')->default(false)->after('email_verified_at');
            $table->string('google_id')->nullable()->unique()->after('password');
            $table->text('two_factor_secret')->nullable()->after('google_id');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->string('active_role', 20)->nullable()->after('two_factor_confirmed_at');
            $table->timestamp('locked_until')->nullable()->after('active_role');
            $table->unsignedSmallInteger('failed_login_attempts')->default(0)->after('locked_until');
            $table->timestamp('last_login_at')->nullable()->after('failed_login_attempts');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->index('active_role');
            $table->index('locked_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['active_role']);
            $table->dropIndex(['locked_until']);
            $table->dropColumn(['email_verified','google_id','two_factor_secret','two_factor_recovery_codes','two_factor_confirmed_at','active_role','locked_until','failed_login_attempts','last_login_at','last_login_ip']);
        });
    }
};
