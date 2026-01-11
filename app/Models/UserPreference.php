<?php
// ============================================
// app/Models/UserPreference.php
// User preferences model
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        // Notification Preferences
        'email_notifications',
        'notify_new_leads',
        'notify_new_reviews',
        'notify_review_replies',
        'notify_verifications',
        'notify_premium_expiring',
        'notify_campaign_updates',
        // Display Preferences
        'theme',
        'language',
        'timezone',
        'date_format',
        'time_format',
        // Privacy Preferences
        'profile_visibility',
        'show_email',
        'show_phone',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'notify_new_leads' => 'boolean',
        'notify_new_reviews' => 'boolean',
        'notify_review_replies' => 'boolean',
        'notify_verifications' => 'boolean',
        'notify_premium_expiring' => 'boolean',
        'notify_campaign_updates' => 'boolean',
        'show_email' => 'boolean',
        'show_phone' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper method to get or create preferences
    public static function getForUser($userId)
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'email_notifications' => true,
                'notify_new_leads' => true,
                'notify_new_reviews' => true,
                'notify_review_replies' => true,
                'notify_verifications' => true,
                'notify_premium_expiring' => true,
                'notify_campaign_updates' => true,
                'theme' => 'system',
                'language' => 'en',
                'timezone' => 'Africa/Lagos',
                'date_format' => 'M j, Y',
                'time_format' => '12h',
                'profile_visibility' => 'public',
                'show_email' => false,
                'show_phone' => false,
            ]
        );
    }
}