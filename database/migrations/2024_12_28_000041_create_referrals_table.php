<?php

// ============================================
// database/migrations/2024_12_28_000041_create_referrals_table.php
// Referral program (user refers others, gets credits)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            
            // Referrer (who referred)
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            
            // Referred (who was referred)
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade');
            
            // Referral Code
            $table->string('referral_code');
            
            // Rewards
            $table->decimal('referrer_reward', 10, 2)->default(0); // Cash reward
            $table->integer('referrer_credits')->default(0); // Ad credits
            $table->decimal('referred_reward', 10, 2)->default(0);
            $table->integer('referred_credits')->default(0);
            
            // Status
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->boolean('rewards_paid')->default(false);
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            $table->index('referrer_id');
            $table->index('referral_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};