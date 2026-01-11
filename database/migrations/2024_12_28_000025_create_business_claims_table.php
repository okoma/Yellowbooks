<?php

// ============================================
// database/migrations/2024_12_28_000025_create_business_claims_table.php
// Claim request workflow - separate from actual claim
// A user requests to claim → Admin reviews → Business gets claimed
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Claim Info
            $table->text('claim_message')->nullable(); // Why they're claiming
            $table->string('claimant_position')->nullable(); // Owner, Manager, Employee
            
            // Contact Verification
            $table->string('verification_phone', 20);
            $table->string('verification_email');
            $table->boolean('phone_verified')->default(false);
            $table->boolean('email_verified')->default(false);
            
            // Status
            $table->enum('status', [
                'pending',           // Waiting for documents
                'documents_submitted', // Documents uploaded, waiting review
                'under_review',      // Admin is reviewing
                'approved',          // Claim approved
                'rejected',          // Claim rejected
                'disputed'           // Multiple claims on same business
            ])->default('pending');
            
            // Admin Review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['business_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_claims');
    }
};