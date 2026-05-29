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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->index(); // Optional unique identifier
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete(); // Assuming clients table
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete(); // Assuming customers table
            $table->string('type')->index(); // order, appointment, etc.
            $table->unsignedBigInteger('reference_id')->nullable()->index(); // Order or Appointment ID
            $table->decimal('amount', 10, 2);
            $table->string('payment_origin'); // card, upi, etc.
            $table->string('currency');
            $table->string('payment_method'); // card, upi, etc.
            $table->string('transaction_status'); // succeeded, failed, etc.
            $table->string('transaction_id')->unique()->nullable(); // PaymentIntent ID
            $table->decimal('paid_amount')->nullable();
            $table->string('paid_currency')->nullable();
            $table->string('stripe_session_id')->unique()->nullable();
            $table->boolean('is_manual')->default(false);
            $table->text('meta')->nullable(); // JSON encoded
            $table->string('invoice_url')->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
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
