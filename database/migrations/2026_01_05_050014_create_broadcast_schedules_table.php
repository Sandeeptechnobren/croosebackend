<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_schedules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('broadcast_id');
            $table->unsignedBigInteger('target_customer')->nullable();

            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');

            $table->timestamps();
            $table->unique(['broadcast_id', 'scheduled_at']);
            $table->foreign('broadcast_id')->references('id')->on('broadcast_headers')->onDelete('cascade');
            $table->foreign('target_customer')->references('id')->on('client_customer')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_schedules');
    }
};
