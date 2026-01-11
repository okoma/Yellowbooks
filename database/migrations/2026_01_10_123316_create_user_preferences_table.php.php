<?php
// ============================================
// database/migrations/2026_01_08_000001_create_user_preferences_table.php
// Separate table for user preferences (Better approach)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Notification Preferences
            $table->boolean('email_notifications')->default(true);
            $table->boolean('notify_new_leads')->default(true);
            $table->boolean('notify_new_reviews')->default(true);
            $table->boolean('notify_review_replies')->default(true);
            $table->boolean('notify_verifications')->default(true);
            $table->boolean('notify_premium_expiring')->default(true);
            $table->boolean('notify_campaign_updates')->default(true);
            
            // Display Preferences
            $table->string('theme')->default('system'); // light, dark, system
            $table->string('language')->default('en');
            $table->string('timezone')->default('Africa/Lagos');
            $table->string('date_format')->default('M j, Y');
            $table->string('time_format')->default('12h'); // 12h, 24h
            
            // Privacy Preferences
            $table->string('profile_visibility')->default('public'); // public, registered, private
            $table->boolean('show_email')->default(false);
            $table->boolean('show_phone')->default(false);
            
            $table->timestamps();
            
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};