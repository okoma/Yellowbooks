<?php

// ============================================
// FILE: 2024_12_28_000043_create_manager_invitations_table.php
// Use Case:
// Owner invites "jane@example.com" to manage Victoria Island branch
// Jane receives email → clicks link → accepts → becomes manager
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
        Schema::create('manager_invitations', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade'); // Which branch
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade'); // Who sent invitation
            
            // Invitation Details
            $table->string('email'); // Email address to invite
            $table->string('invitation_token')->unique(); // Secure token for invitation link
            $table->string('position')->default('Branch Manager'); // Offered position title
            
          
            // These permissions will be assigned when invitation is accepted
            $table->json('permissions')->nullable();
            // Same structure as branch_managers.permissions
            
            // Status Tracking
            $table->enum('status', [
                'pending',   // Sent, waiting for response
                'accepted',  // Invitee accepted invitation
                'declined',  // Invitee declined invitation
                'expired'    // Invitation expired (not responded within time limit)
            ])->default('pending');
            
            // Expiration
            $table->timestamp('expires_at'); // Usually 7 days from sent
            $table->timestamp('accepted_at')->nullable(); // When they accepted
          
            // If new user signs up, link after registration
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['email', 'status']); // Find pending invitations for an email
            $table->index('invitation_token'); // Quick token lookup
            $table->index('expires_at'); // Find expired invitations to clean up
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_invitations');
    }
};