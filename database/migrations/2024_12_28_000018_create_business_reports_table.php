<?php

// ============================================
// database/migrations/2024_12_28_000018_create_business_reports_table.php
// Users can report businesses for violations
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade');
            
            // Report Details
            $table->enum('reason', [
                'spam',              // Spam/fake listing
                'duplicate',         // Duplicate business
                'inappropriate',     // Inappropriate content
                'wrong_info',        // Incorrect information
                'closed',            // Business is closed
                'offensive',         // Offensive content
                'fake_reviews',      // Fake reviews suspected
                'other'              // Other reason
            ]);
            
            $table->text('description')->nullable(); // Detailed explanation
            $table->json('evidence')->nullable(); // Screenshots/proof URLs
            
            // Status
            $table->enum('status', [
                'pending',    // Awaiting review
                'reviewing',  // Under investigation
                'resolved',   // Issue resolved
                'dismissed',  // Report dismissed
                'actioned'    // Action taken (business suspended/deleted)
            ])->default('pending');
            
            // Admin Response
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            // Action Taken
            $table->enum('action_taken', [
                'none',
                'warning_sent',
                'content_removed',
                'business_suspended',
                'business_deleted',
                'user_contacted'
            ])->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['business_id', 'status']);
            $table->index(['reported_by', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_reports');
    }
};