<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordiio_license_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description');
            $table->json('allowed_usage')->nullable();
            $table->text('restrictions')->nullable();
            $table->enum('price_model', ['pay_per_track', 'subscription', 'custom'])->default('pay_per_track');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
