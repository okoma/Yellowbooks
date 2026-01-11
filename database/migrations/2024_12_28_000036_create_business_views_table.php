<?php

// ============================================
// database/migrations/2024_12_28_000016_create_business_views_table.php
// Track every view with referral source and location
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade');
            
            // Referral Source (WHERE they came from)
            $table->enum('referral_source', [
                'yellowbooks',  // Internal YellowBooks navigation
                'google',       // Google Search
                'bing',         // Bing Search
                'facebook',     // Facebook
                'instagram',    // Instagram
                'twitter',      // Twitter/X
                'linkedin',     // LinkedIn
                'direct',       // Direct URL visit
                'other'         // Other sources
            ])->default('direct');
            
            // Visitor Location (IP-based)
            $table->string('country')->default('Unknown');
            $table->string('country_code', 2)->nullable(); // NG, US, UK
            $table->string('region')->default('Unknown'); // Lagos State
            $table->string('city')->default('Unknown'); // Ikeja
            $table->string('ip_address', 45)->nullable(); // IPv4 or IPv6
            
            // User Agent (optional)
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable(); // mobile, desktop, tablet
            
            // Timestamps for hourly tracking
            $table->timestamp('viewed_at')->useCurrent();
            $table->date('view_date'); // For daily grouping
            $table->string('view_hour', 2); // 00-23 for hourly stats
            $table->string('view_month', 7); // YYYY-MM for monthly stats
            $table->string('view_year', 4); // YYYY for yearly stats
            
            $table->timestamps();
            
            // Indexes for fast queries
            $table->index(['business_branch_id', 'referral_source']);
            $table->index(['business_branch_id', 'view_date']);
            $table->index(['business_branch_id', 'view_month']);
            $table->index(['referral_source', 'view_date']);
            $table->index(['country_code', 'view_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_views');
    }
};