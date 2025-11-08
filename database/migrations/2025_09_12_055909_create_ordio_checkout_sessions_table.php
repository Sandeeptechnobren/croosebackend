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
        Schema::create('ordio_checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('mode',255);
            $table->integer('customer_id');
            $table->integer('client_reference_id');
            $table->string('customer_email',255);
            $table->text('metadata');
            $table->enum('status',['complete','pending'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordio_checkout_sessions');
    }
};
