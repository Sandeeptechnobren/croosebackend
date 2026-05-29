<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('space_id')->constrained()->onDelete('cascade');
            $table->string('name'); 
            $table->text('description')->nullable();
            $table->integer('duration_minutes');
            $table->decimal('price', 10, 2);
            $table->string('currency')->nullable();
            $table->string('unit')->nullable(); 
            $table->string('category')->nullable();
            $table->enum('type', ['in_store', 'at_home', 'virtual'])->default('in_store');
            $table->integer('buffer_minutes')->default(0); 
            $table->json('available_days')->nullable(); 
            $table->json('ai_tags')->nullable();        
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
