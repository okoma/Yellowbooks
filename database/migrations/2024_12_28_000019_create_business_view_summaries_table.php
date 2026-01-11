<?php

// ============================================
// database/migrations/2024_12_28_000019_create_business_view_summaries_table.php
// Aggregated hourly/daily stats for faster queries
// This can be populated via a scheduled job
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_view_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade');
            
            // Time Period
            $table->enum('period_type', ['hourly', 'daily', 'monthly', 'yearly']);
            $table->string('period_key'); // "2024-12-28-14", "2024-12-28", "2024-12", "2024"
            
            // Aggregated Stats
            $table->integer('total_views')->default(0);
            $table->json('views_by_source')->nullable(); // {"yellowbooks": 100, "google": 50}
            $table->json('views_by_country')->nullable(); // {"NG": 80, "US": 20}
            
            // Interaction Stats (optional, can be separate table)
            $table->integer('total_calls')->default(0);
            $table->integer('total_whatsapp')->default(0);
            $table->integer('total_emails')->default(0);
            $table->integer('total_website_clicks')->default(0);
            $table->integer('total_map_clicks')->default(0);
            
            $table->timestamps();
            
            // Unique constraint - one record per branch per period
            $table->unique(['business_branch_id', 'period_type', 'period_key'], 'branch_period_unique');
            
            // Indexes
            $table->index(['business_branch_id', 'period_type']);
            $table->index('period_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_view_summaries');
    }
};