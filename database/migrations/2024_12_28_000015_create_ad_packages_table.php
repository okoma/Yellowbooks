<?php

// ============================================
// database/migrations/2024_12_28_000036_create_ad_packages_table.php
// Pre-defined ad campaign packages
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_packages', function (Blueprint $table) {
            $table->id();
            
            // Package Details
            $table->string('name'); // "Starter", "Growth", "Premium"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('NGN');
            
            // Campaign Type
            $table->enum('campaign_type', ['bump_up', 'sponsored', 'featured']);
            
            // Duration
            $table->integer('duration_days'); // How many days
            
            // Budget/Limits
            $table->integer('impressions_limit')->nullable(); // Max impressions
            $table->integer('clicks_limit')->nullable(); // Max clicks
            
            // Features
            $table->json('features')->nullable();
            // Example: ["Top placement", "Homepage banner", "Email blast"]
            
            // Display
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('slug');
            $table->index('campaign_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_packages');
    }
};