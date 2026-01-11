<?php

// ============================================
// database/migrations/2024_12_28_000035_create_user_payment_methods_table.php
// Saved payment methods for users (cards, bank accounts)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Payment Method Type
            $table->enum('type', ['card', 'bank_account']);
            
            // Card Details (if type = card)
            $table->string('card_brand')->nullable(); // Visa, Mastercard
            $table->string('card_last4')->nullable(); // Last 4 digits
            $table->string('card_exp_month', 2)->nullable();
            $table->string('card_exp_year', 4)->nullable();
            
            // Bank Details (if type = bank_account)
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            
            // Gateway Details
            $table->string('payment_gateway')->nullable(); // paystack, flutterwave
            $table->string('gateway_customer_code')->nullable();
            $table->string('authorization_code')->nullable(); // For recurring payments
            
            // Status
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_payment_methods');
    }
};