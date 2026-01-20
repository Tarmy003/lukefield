<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_and_current_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('customerid', 20)->index();
            $table->string('old_acct_no', 30)->unique()->index();
            $table->string('branch', 100)->nullable();
            $table->string('product_code', 20)->nullable();
            $table->decimal('bk_balance_corrected', 18, 2)->default(0);
            $table->decimal('accrued_till_date', 18, 2)->nullable();
            $table->date('last_accrued_date')->nullable();
            $table->string('lien_status', 10)->nullable();
            $table->tinyInteger('account_status')->default(1);
            $table->tinyInteger('account_tier')->default(1);
            $table->timestamp('date_opened')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['customerid', 'old_acct_no']);
            $table->index('account_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_and_current_accounts');
    }
};