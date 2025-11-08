<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('subscription_id')->index();
            $table->unsignedBigInteger('space_id')->index();

            $table->date('start_date');
            $table->date('end_date');

            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->enum('renewal_type', ['manual', 'auto'])->default('manual');

            $table->string('payment_reference')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            $table->foreign('space_id')->references('id')->on('spaces')->onDelete('cascade');

            $table->index(['customer_id', 'space_id', 'status'], 'cust_space_status_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('customer_subscriptions');
    }
};
