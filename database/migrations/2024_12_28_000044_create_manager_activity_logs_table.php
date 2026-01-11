<?php

// ============================================
// FILE: 2024_12_28_000044_create_manager_activity_logs_table.php
// Use Case:
// Manager changes product price from ₦5,000 to ₦7,000
// Owner sees in activity log: "John updated Chicken Wings price"
// Can see old value (5000) and new value (7000)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('manager_activity_logs', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('branch_manager_id')->constrained()->onDelete('cascade'); // Who did it
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade'); // Which branch
            
            // Action Details
            $table->string('action'); // Action type identifier
            $table->text('description'); // Human-readable description
    
            // Can link to any model: Product, Review, Lead, BusinessBranch, etc.
            $table->morphs('actionable', 'mgr_act_logs'); // Short index prefix to avoid duplicate name

            // Store what changed for audit purposes
            $table->json('old_values')->nullable(); // Values before change
            $table->json('new_values')->nullable(); // Values after change
            
            // Context Information
            $table->string('ip_address', 45)->nullable(); // IPv4 or IPv6
            $table->text('user_agent')->nullable(); // Browser/device info
            
            $table->timestamps();
            
            // Indexes
            $table->index(['branch_manager_id', 'created_at']); // Get activity history for a manager
            $table->index(['business_branch_id', 'action']); // Filter by action type for a branch
            $table->index('created_at'); // Time-based queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_activity_logs');
    }
};
