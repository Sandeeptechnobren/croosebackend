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
         Schema::create('delay_dog_journeys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table ->foreignId('user_id')
                    ->constrained('delay_dog_user_details')
                    ->onDelete('cascade');
            $table->string('origin_station');
            $table->string('destination_station');
            $table->date('journey_date');
            $table->boolean('was_delayed')->default(false);
            $table->integer('delay_minutes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delay_dog_journeys');
    }
};
