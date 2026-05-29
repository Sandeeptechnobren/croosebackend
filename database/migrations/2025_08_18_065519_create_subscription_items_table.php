<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void 
    {
        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id')->index();
            $table->enum('item_type', ['product', 'service']);
            $table->unsignedBigInteger('item_id');
            $table->json('extra_benefit')->nullable();
            $table->timestamps();
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            $table->unique(['subscription_id', 'item_type', 'item_id'], 'subscription_item_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('subscription_items');
    }
};
