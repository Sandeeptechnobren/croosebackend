<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('space_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('subscription_type', ['general', 'product', 'service'])->default('general');
            $table->enum('variant', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->enum('access_type', ['free_access', 'pay_individually', 'discount'])->default('pay_individually');
            $table->unsignedTinyInteger('discount_rate')->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->json('benefits')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->foreign('space_id')->references('id')->on('spaces')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('subscriptions');
    }
};
