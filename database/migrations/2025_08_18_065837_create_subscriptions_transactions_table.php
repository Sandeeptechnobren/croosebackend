<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subscription_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('customer_subscription_id')->index();

            $table->decimal('amount', 12, 2);
            $table->string('currency', 10);

            $table->enum('payment_gateway', ['stripe', 'paystack', 'momo', 'other'])->default('other');
            $table->enum('transaction_status', ['success', 'failed', 'pending', 'refunded'])->default('pending');

            $table->timestamp('transaction_date')->useCurrent();
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->foreign('customer_subscription_id', 'fk_csub_txn')
                ->references('id')->on('customer_subscriptions')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('subscription_transactions');
    }
};
