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
                $table->id();
                $table->string('name');
                $table->string('whatsapp_number')->unique();
                $table->string('email')->nullable();
                $table->string('address');
                $table->integer('pin_code');
                $table->json('meta')->nullable(); // for dynamic info
                $table->timestamps();
            });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
