<?php

// ============================================
// database/migrations/2024_12_28_000003_create_businesses_table.php
// Main business (parent) - holds shared info
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Core Business Info (shared across branches)
            $table->string('business_name'); // "Chicken Republic"
            $table->string('slug')->unique(); // "chicken-republic"
            $table->text('description')->nullable();
            $table->string('registration_number')->nullable(); // CAC/RC number
            
            // Business Details
            $table->integer('years_in_business')->default(0);
            $table->string('entity_type')->nullable(); // LLC, Sole Proprietorship, etc.
            
            // Global Contact (optional - can be overridden by branches)
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('website')->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->text('whatsapp_message')->nullable(); // Pre-filled message
            
            // Media (shared)
            $table->string('logo')->nullable();
            $table->string('cover_photo')->nullable();
            
            // Claim Status
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->foreignId('claimed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_verified')->default(false);
            
            // Verification fields
            $table->enum('verification_level', [
                'none',      // Not verified at all
                'basic',     // CAC only
                'standard',  // CAC + Location + Email
                'premium'    // CAC + Location + Email + Website
            ])->default('none');
            $table->integer('verification_score')->default(0);
            $table->timestamp('last_verification_attempt')->nullable();
            $table->unsignedBigInteger('current_verification_id')->nullable();
            
            $table->enum('status', ['draft', 'pending_review', 'active', 'suspended', 'closed'])->default('pending_review');
            
            // Premium Features (applies to all branches)
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_until')->nullable();
            
            // Aggregated Stats (from all branches)
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_views')->default(0);
            $table->integer('total_leads')->default(0);
            $table->integer('total_saves')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('slug');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};