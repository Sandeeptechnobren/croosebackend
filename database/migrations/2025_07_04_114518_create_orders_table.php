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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('order_quantity');
            $table->integer('order_amount');
            $table->string('payment_status')->nullable();
            $table->string('payment_origin')->nullable();
            $table->string('payment_method')->nullable();
            $table->enum('status',['pending', 'confirm', 'cancelled', 'complete'])->default('pending'); // or enum: ['pending', 'confirmed', 'cancelled', 'completed']
            $table->string('address');
            $table->text('notes')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
