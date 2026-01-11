<?php

// ============================================
// database/migrations/2024_12_28_000039_create_coupons_table.php
// Discount codes for subscriptions and ad campaigns
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            
            // Coupon Details
            $table->string('code')->unique();
            $table->text('description')->nullable();
            
            // Discount
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('max_discount', 10, 2)->nullable(); // Max discount amount for percentage
            
            // Applicable To
            $table->enum('applies_to', ['all', 'subscriptions', 'ad_campaigns']);
            $table->json('applicable_plans')->nullable(); // Specific plan IDs
            
            // Usage Limits
            $table->integer('usage_limit')->nullable(); // Total times it can be used
            $table->integer('usage_limit_per_user')->default(1);
            $table->integer('times_used')->default(0);
            
            // Validity
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            
            // Minimum Purchase
            $table->decimal('min_purchase_amount', 10, 2)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index(['is_active', 'valid_from', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};