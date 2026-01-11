<?php

// ============================================
// database/migrations/2024_12_28_000015_create_ad_campaigns_table.php
// YellowBooks advertising (managed by YellowBooks admin)
// Businesses PAY for these to boost their listing
// UPDATED: Only YellowBooks referrals count toward billing
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchased_by')->constrained('users')->onDelete('cascade'); // Who paid
            
            // Package and transaction references
            $table->foreignId('ad_package_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            
            // Campaign Type
            $table->enum('type', ['bump_up', 'sponsored', 'featured'])->default('bump_up');
            // bump_up = Move to top of search results
            // sponsored = Show in sponsored section
            // featured = Homepage featured section
            
            // Campaign Details
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable();
            
            // Targeting (optional)
            $table->json('target_locations')->nullable(); // Which cities to show
            $table->json('target_categories')->nullable(); // Which categories
            
            // Duration & Budget
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->decimal('budget', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_paid')->default(false);
            
            // Performance Stats - UPDATED FOR YELLOWBOOKS-ONLY BILLING
            // Total stats (all referral sources) - for analytics only
            $table->integer('total_impressions')->default(0); // Times shown (all sources)
            $table->integer('total_clicks')->default(0); // Times clicked (all sources)
            
            // YellowBooks-only stats (for billing)
            $table->integer('yellowbooks_impressions')->default(0); // Only YellowBooks referrals
            $table->integer('yellowbooks_clicks')->default(0); // Only YellowBooks referrals
            
            // Breakdown by source (JSON) - for detailed analytics
            $table->json('impressions_by_source')->nullable();
            // Example: {"yellowbooks": 1500, "google": 800, "direct": 200}
            
            $table->json('clicks_by_source')->nullable();
            // Example: {"yellowbooks": 50, "google": 30, "direct": 10}
            
            // Budget & Billing (based on YellowBooks traffic only)
            $table->decimal('cost_per_impression', 8, 4)->default(0);
            $table->decimal('cost_per_click', 8, 4)->default(0);
            $table->decimal('total_spent', 10, 2)->default(0); // Only counts YellowBooks traffic
            
            // CTR (Click-through rate)
            $table->decimal('ctr', 5, 2)->default(0); // Overall CTR
            $table->decimal('yellowbooks_ctr', 5, 2)->default(0); // YellowBooks-only CTR
            
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};