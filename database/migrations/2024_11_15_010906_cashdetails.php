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
        Schema::create('cash', function (Blueprint $table) {
            $table->id('Cash_ID ');
            $table->int('Admin_ID');
            $table->int('Staff_ID');
            $table->int('Initial_amount');
            $table->int('Remittance');
            $table->date('Datetime_InitialAmo'); 
            $table->date('Datetime_Remittance');
            $table->date('Received_datetime');
            $table->string('Email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('Password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash');
    }
};
