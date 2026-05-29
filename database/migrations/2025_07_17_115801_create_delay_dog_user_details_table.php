<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
         Schema::create('delay_dog_user_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('full_name', 100);
            $table->string('email', 150)->unique();
            $table->string('phone_number', 15)->unique();
            $table->boolean('is_monthly_railcard')->default(false);
            $table->string('railcard_image_path', 255)->nullable();
            $table->string('usual_origin', 100);
            $table->string('usual_destination', 100);
            $table->timestamp('registered_at')->useCurrent();
            $table->date('last_daily_check')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('DelayDogUserDetail');
    }
};
