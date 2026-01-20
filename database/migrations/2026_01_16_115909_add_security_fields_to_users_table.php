<?php
// database/migrations/xxxx_add_security_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('two_factor_enabled')->default(true)->after('is_active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->json('trusted_ips')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'is_active', 'two_factor_enabled', 
                'last_login_at', 'last_login_ip', 'failed_login_attempts',
                'locked_until', 'device_fingerprint', 'trusted_ips'
            ]);
            $table->dropSoftDeletes();
        });
    }
};