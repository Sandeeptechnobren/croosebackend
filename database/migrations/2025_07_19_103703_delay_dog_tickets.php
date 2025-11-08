<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('delay_dog_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained('delay_dog_journeys')->onDelete('cascade');
            $table->string('ticket_image_path');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('delay_dog_tickets');
    }
};
