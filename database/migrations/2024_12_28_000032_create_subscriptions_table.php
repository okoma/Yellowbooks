<?php

// ============================================
// database/migrations/2024_12_28_000032_create_subscriptions_table.php
// User's active subscription
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('restrict');
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade'); // If subscription is for a specific business
            
            // Subscription Details
            $table->string('subscription_code')->unique()->nullable(); // From payment gateway
            $table->enum('status', [
                'active',
                'trialing',
                'past_due',
                'cancelled',
                'expired',
                'paused'
            ])->default('active');
            
            // Billing Period
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            
            // Auto-renewal
            $table->boolean('auto_renew')->default(true);
            $table->text('cancellation_reason')->nullable();
            
            // Usage Tracking
            $table->integer('branches_used')->default(0);
            $table->integer('products_used')->default(0);
            $table->integer('team_members_used')->default(0);
            $table->integer('photos_used')->default(0);
            $table->integer('ad_credits_used')->default(0);
            
            // Payment Method
            $table->string('payment_method')->nullable(); // 'card', 'bank_transfer', 'paystack', etc.
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index(['business_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};