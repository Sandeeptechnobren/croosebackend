<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delay_dog_claims', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // ✅ UUID to be auto-generated in model
            $table->foreignId('journey_id')->constrained('delay_dog_journeys')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('delay_dog_user_details')->onDelete('cascade');
            $table->string('claim_reference')->nullable();
            $table->enum('status', ['pending', 'submitted', 'processing', 'approved', 'rejected', 'paid'])->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->json('response_data')->nullable(); // ✅ Must store valid JSON
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delay_dog_claims');
    }
};
