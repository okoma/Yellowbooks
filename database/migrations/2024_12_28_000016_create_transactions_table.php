<?php

// ============================================
// database/migrations/2024_12_28_000033_create_transactions_table.php
// All payment transactions (subscriptions, ad campaigns, etc.)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Transaction Details
            $table->string('transaction_ref')->unique(); // Unique reference
            $table->string('payment_gateway_ref')->nullable(); // Paystack/Flutterwave reference
            
            // What was purchased (polymorphic)
            $table->morphs('transactionable'); // Can be subscription, ad_campaign, etc.
            
            // Amount
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('NGN');
            $table->decimal('exchange_rate', 10, 4)->default(1); // If foreign currency
            
            // Payment Method
            $table->enum('payment_method', [
                'card',
                'bank_transfer',
                'ussd',
                'paystack',
                'flutterwave',
                'stripe',
                'paypal',
                'wallet',
                'manual'
            ]);
            
            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'refunded',
                'cancelled'
            ])->default('pending');
            
            // Payment Details
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Extra payment data
            
            // Gateway Response
            $table->json('gateway_response')->nullable();
            $table->string('authorization_code')->nullable(); // For recurring payments
            
            // Refund Info
            $table->boolean('is_refunded')->default(false);
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            
            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index('transaction_ref');
            $table->index('payment_gateway_ref');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};