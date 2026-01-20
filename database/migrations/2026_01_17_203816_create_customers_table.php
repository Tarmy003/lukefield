<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id')->unique()->index();
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('surname');
            $table->string('other_name')->nullable();
            $table->string('customer_type')->nullable();
            $table->string('sex')->nullable();
            $table->string('state_of_origin')->nullable();
            $table->date('dob')->nullable();
            $table->text('residential_address')->nullable();
            $table->string('residential_state')->nullable();
            $table->string('residential_town')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('business_phone_no')->nullable();
            $table->string('office_name')->nullable();
            $table->text('office_address')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('acct_officer')->nullable();
            $table->string('occupation')->nullable();
            $table->string('edu_level')->nullable();
            $table->string('status')->default('Active');
            $table->date('date_opened')->nullable();
            $table->string('bvn')->nullable();
            $table->string('nin')->nullable();
            $table->string('tin')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_no')->nullable();
            $table->date('id_issue_date')->nullable();
            $table->date('id_expiry_date')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('next_of_kin')->nullable();
            $table->string('next_of_kin_phone_no')->nullable();
            $table->text('next_of_kin_address')->nullable();
            $table->string('reg_no')->nullable();
            $table->string('sector')->nullable();
            $table->string('pep')->default('NO');
            $table->text('pep_details')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};