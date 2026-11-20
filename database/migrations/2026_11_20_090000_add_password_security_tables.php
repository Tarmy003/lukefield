<?php
// database/migrations/2026_11_20_090000_add_password_security_tables.php
//
// Password security for the Archive Repository:
//  - users.password_changed_at   drives the 90-day expiry
//  - users.must_change_password  forces a change at the next sign-in
//  - password_histories          blocks reuse of the last passwords
//  - password_setup_tokens       single-use emailed links (reset and welcome),
//                                stored hashed, with who issued each one

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('password');
            }
        });

        if (! Schema::hasTable('password_histories')) {
            Schema::create('password_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('password');
                $table->timestamp('created_at')->nullable();

                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('password_setup_tokens')) {
            Schema::create('password_setup_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('token_hash', 64)->unique();
                $table->string('purpose', 20); // reset | welcome
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'used_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_setup_tokens');
        Schema::dropIfExists('password_histories');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'must_change_password')) {
                $table->dropColumn('must_change_password');
            }
            if (Schema::hasColumn('users', 'password_changed_at')) {
                $table->dropColumn('password_changed_at');
            }
        });
    }
};
