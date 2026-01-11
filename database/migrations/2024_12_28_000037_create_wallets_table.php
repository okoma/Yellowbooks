<?php

// ============================================
// database/migrations/2024_12_28_000037_create_wallets_table.php
// User wallet for ad credits, refunds, etc.
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('currency')->default('NGN');
            
            // Ad Credits (separate from cash balance)
            $table->integer('ad_credits')->default(0); // Can be used for ad campaigns
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};