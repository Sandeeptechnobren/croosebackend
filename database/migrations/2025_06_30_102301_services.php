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
                    Schema::create('services', function (Blueprint $table) {
                    $table->id();
                    
                    $table->foreignId('client_id')->constrained()->onDelete('cascade'); // Links to clients table

                    $table->string('name');
                    $table->string('slug')->unique()->nullable(); // Optional AI-friendly or URL slug
                    $table->text('description')->nullable();

                    $table->integer('duration_minutes');
                    $table->decimal('price', 10, 2);
                    $table->string('unit')->nullable(); // session, hr, etc.

                    $table->string('category')->nullable();
                    $table->enum('type', ['in_store', 'at_home', 'virtual'])->default('in_store');

                    $table->integer('buffer_minutes')->default(0); // Break before/after service (optional)

                    $table->json('available_days')->nullable(); // e.g., ["mon", "wed"]
                    $table->json('ai_tags')->nullable();        // AI search helper tags

                    $table->boolean('is_active')->default(true);
                    $table->boolean('is_featured')->default(false);

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
