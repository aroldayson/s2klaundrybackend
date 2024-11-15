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
        Schema::create('addlaundry_services', function (Blueprint $table) {
            $table->id('Addlaundryserv_ID');
            $table->string('TransacDet_ID');
            $table->string('AddLaundryServ_name');
            $table->string('AddLaundryServ_price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addlaundry_services');
    }
};
