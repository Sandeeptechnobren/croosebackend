<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('space_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('whatsapp_number', 255);
            $table->text('user_message');
            $table->text('bot_response');
            $table->string('session_id', 255);
            $table->string('current_step', 100)->nullable();
            $table->string('intent_detected', 50)->nullable();
            $table->json('context_data')->nullable();
            $table->timestamp('message_timestamp')->useCurrent();
            $table->timestamps();
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('space_id')->references('id')->on('spaces')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }
    public function down(): void
    {
    }
};
