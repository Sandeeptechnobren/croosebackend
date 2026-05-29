<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Space_whapi_payment_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('space_id')->nullable();
            $table->string('type')->default('whapinstance');
            $table->string('reference_id')->unique();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('payment_origin')->nullable();
            $table->string('currency')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_status')->nullable();
            $table->string('transaction_id')->unique()->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->text('meta')->nullable();
            $table->string('invoice_url')->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('space_id')->references('id')->on('spaces')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_whapi_payment_details');
    }
};
