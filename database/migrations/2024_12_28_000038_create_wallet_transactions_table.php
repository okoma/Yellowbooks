<?php

// ============================================
// database/migrations/2024_12_28_000038_create_wallet_transactions_table.php
// Track all wallet movements (deposits, withdrawals, usage)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Transaction Type
            $table->enum('type', [
                'deposit',           // Added money
                'withdrawal',        // Withdrew money
                'purchase',          // Bought something
                'refund',            // Refund received
                'bonus',             // Free credits/bonus
                'credit_purchase',   // Bought ad credits
                'credit_usage'       // Used ad credits
            ]);
            
            // Amount
            $table->decimal('amount', 10, 2)->default(0); // Cash amount
            $table->integer('credits', false, true)->default(0); // Ad credits
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->integer('credits_before')->default(0);
            $table->integer('credits_after')->default(0);
            
            // Description
            $table->text('description');
            
            // Reference (polymorphic)
            $table->morphs('reference'); // Can link to transaction, ad_campaign, etc.
            
            $table->timestamps();
            
            $table->index(['wallet_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};