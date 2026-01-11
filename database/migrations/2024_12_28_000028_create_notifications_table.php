<?php
// ============================================
// database/migrations/2024_12_28_000028_create_notifications_table.php
// User notifications - UPDATED with UUID support
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            // UUID primary key (Filament requirement)
            $table->uuid('id')->primary();
            
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Notification Type
            $table->enum('type', [
                'claim_submitted',
                'claim_approved',
                'claim_rejected',
                'verification_submitted',
                'verification_approved',
                'verification_rejected',
                'verification_resubmission_required',
                'new_review',
                'review_reply',
                'new_lead',
                'business_reported',
                'premium_expiring',
                'campaign_ending',
                'system'
            ]);
            
            // Notification Content
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            
            // Related Records (polymorphic)
            $table->morphs('notifiable');
            
            // Filament data field
            $table->json('data')->nullable();
            
            // Status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};