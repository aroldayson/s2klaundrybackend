<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id('Cust_ID');
            $table->string('Cust_lname');
            $table->string('Cust_fname');
            $table->string('Cust_mname');
            $table->string('Cust_phoneno');
            $table->string('Cust_address');
            $table->string('Cust_image');
            $table->string('Cust_email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('Cust_password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
