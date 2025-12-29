<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Ordiio_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('license_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('payment_origin')->nullable();
            $table->string('currency')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_status')->default('pending');
            $table->string('transaction_id')->nullable();
            $table->decimal('paid_amount', 10, 0)->nullable();
            $table->string('paid_currency', 36)->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->text('meta')->nullable();
            $table->string('invoice_url')->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

    }
    public function down(): void
    {
    }
};
