<?php

// ============================================
// database/migrations/2024_12_28_000031_create_subscription_plans_table.php
// Define pricing tiers (Free, Basic, Pro, Enterprise)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Free", "Basic", "Pro", "Enterprise"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('price', 10, 2)->default(0); // Monthly price
            $table->decimal('yearly_price', 10, 2)->nullable(); // Annual discount
            $table->string('currency')->default('NGN');
            
            // Billing
            $table->enum('billing_interval', ['monthly', 'yearly', 'lifetime'])->default('monthly');
            $table->integer('trial_days')->default(0); // Free trial period
            
            // Features (JSON)
            $table->json('features')->nullable();
            // Example: {
            //   "max_branches": 5,
            //   "max_products": 100,
            //   "analytics": true,
            //   "priority_support": false,
            //   "featured_listing": false,
            //   "remove_ads": true,
            //   "custom_domain": false
            // }
            
            // Limits
            $table->integer('max_branches')->nullable(); // null = unlimited
            $table->integer('max_products')->nullable();
            $table->integer('max_team_members')->nullable();
            $table->integer('max_photos')->nullable();
            
            // Ad Campaign Credits (optional)
            $table->integer('monthly_ad_credits')->default(0); // Free ad credits per month
            
            // Display
            $table->boolean('is_popular')->default(false); // "Most Popular" badge
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};