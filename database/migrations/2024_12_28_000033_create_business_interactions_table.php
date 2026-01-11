<?php

// ============================================
// database/migrations/2024_12_28_000017_create_business_interactions_table.php
// Track clicks on Call, WhatsApp, Email, Website, Map
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // If logged in
            
            // Interaction Type
            $table->enum('interaction_type', [
                'call',       // Clicked phone number
                'whatsapp',   // Clicked WhatsApp button
                'email',      // Clicked email
                'website',    // Clicked website link
                'map',        // Clicked map/location
                'directions'  // Clicked get directions
            ]);
            
            // Referral Source (same as views)
            $table->enum('referral_source', [
                'yellowbooks',
                'google',
                'bing',
                'facebook',
                'instagram',
                'twitter',
                'linkedin',
                'direct',
                'other'
            ])->default('direct');
            
            // Visitor Location
            $table->string('country')->default('Unknown');
            $table->string('country_code', 2)->nullable();
            $table->string('region')->default('Unknown');
            $table->string('city')->default('Unknown');
            $table->string('ip_address', 45)->nullable();
            
            // Device Info
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable();
            
            // Timestamps
            $table->timestamp('interacted_at')->useCurrent();
            $table->date('interaction_date');
            $table->string('interaction_hour', 2);
            $table->string('interaction_month', 7);
            $table->string('interaction_year', 4);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['business_branch_id', 'interaction_type']);
            $table->index(['business_branch_id', 'referral_source']);
            $table->index(['business_branch_id', 'interaction_date']);
            $table->index(['interaction_type', 'interaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_interactions');
    }
};