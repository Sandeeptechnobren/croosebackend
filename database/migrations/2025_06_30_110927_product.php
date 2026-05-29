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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('space_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency')->nullable();
            $table->string('unit')->nullable(); // e.g., piece, kg, bottle
            $table->enum('type', ['physical', 'digital', 'service_addon'])->default('physical');
            $table->integer('stock')->nullable();
            $table->string('sku')->nullable();
            $table->string('category')->nullable();
            $table->string('image')->nullable(); // Path to product image
            $table->json('tags')->nullable(); // AI tags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
