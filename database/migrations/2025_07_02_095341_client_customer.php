<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_customer', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            $table->timestamp('first_interaction_at')->nullable();
            $table->string('source')->nullable(); // 'appointment' or 'order'

            $table->timestamps();

            $table->unique(['client_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_customer');
    }
};
