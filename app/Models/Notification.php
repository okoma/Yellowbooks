<?php
// ============================================
// app/Models/Notification.php
// User notifications - Filament Compatible with UUIDs
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;  // ← ADDED HasUuids trait

    // Specify that this model uses UUIDs
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'action_url',
        'notifiable_type',
        'notifiable_id',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    // ============================================
    // QUERY SCOPES
    // ============================================

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->latest()->limit($limit);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFilament($query)
    {
        return $query->whereNotNull('data')
            ->whereRaw("JSON_EXTRACT(data, '$.format') = 'filament'");
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return $this;
    }

    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);

        return $this;
    }

    public function isRead(): bool
    {
        return $this->is_read;
    }

    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    public function getIcon(): string
    {
        return match($this->type) {
            'claim_submitted' => 'heroicon-o-document-text',
            'claim_approved' => 'heroicon-o-check-circle',
            'claim_rejected' => 'heroicon-o-x-circle',
            'verification_submitted' => 'heroicon-o-shield-check',
            'verification_approved' => 'heroicon-o-badge-check',
            'verification_rejected' => 'heroicon-o-shield-exclamation',
            'verification_resubmission_required' => 'heroicon-o-arrow-path',
            'new_review' => 'heroicon-o-star',
            'review_reply' => 'heroicon-o-chat-bubble-left',
            'new_lead' => 'heroicon-o-user-plus',
            'business_reported' => 'heroicon-o-flag',
            'premium_expiring' => 'heroicon-o-clock',
            'campaign_ending' => 'heroicon-o-megaphone',
            'system' => 'heroicon-o-bell',
            default => 'heroicon-o-bell',
        };
    }

    public function getColor(): string
    {
        return match($this->type) {
            'claim_approved', 'verification_approved' => 'success',
            'claim_rejected', 'verification_rejected' => 'danger',
            'verification_resubmission_required' => 'warning',
            'new_review', 'review_reply', 'new_lead' => 'info',
            'business_reported' => 'danger',
            'premium_expiring', 'campaign_ending' => 'warning',
            default => 'gray',
        };
    }

    // ============================================
    // STATIC FACTORY METHODS
    // ============================================

    public static function send(
        $userId, 
        $type, 
        $title, 
        $message, 
        $actionUrl = null, 
        $notifiable = null, 
        $extraData = []
    ) {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notifiable_id' => $notifiable?->id,
            'data' => array_merge([
                'format' => 'filament',
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
                'type' => $type,
            ], $extraData),
        ]);
    }

    public static function claimSubmitted($userId, $businessName, $claimId)
    {
        return static::send(
            userId: $userId,
            type: 'claim_submitted',
            title: 'Business Claim Submitted',
            message: "Your claim for {$businessName} has been submitted and is awaiting review.",
            actionUrl: "/admin/claims/{$claimId}",
            extraData: ['business_name' => $businessName, 'claim_id' => $claimId]
        );
    }

    public static function claimApproved($userId, $businessName, $businessId)
    {
        return static::send(
            userId: $userId,
            type: 'claim_approved',
            title: 'Business Claim Approved! 🎉',
            message: "Your claim for {$businessName} has been approved. You can now manage your business.",
            actionUrl: "/dashboard/businesses/{$businessId}",
            extraData: ['business_name' => $businessName, 'business_id' => $businessId]
        );
    }

    public static function newReview($userId, $businessName, $rating, $reviewId)
    {
        $stars = str_repeat('⭐', $rating);
        
        return static::send(
            userId: $userId,
            type: 'new_review',
            title: 'New Review Received',
            message: "Your business {$businessName} received a {$rating}-star review {$stars}",
            actionUrl: "/dashboard/reviews/{$reviewId}",
            extraData: ['business_name' => $businessName, 'rating' => $rating, 'review_id' => $reviewId]
        );
    }

    public static function newLead($userId, $businessName, $clientName, $leadId)
    {
        return static::send(
            userId: $userId,
            type: 'new_lead',
            title: 'New Lead Received! 🎯',
            message: "{$clientName} is interested in {$businessName}. Respond quickly to convert!",
            actionUrl: "/dashboard/leads/{$leadId}",
            extraData: ['business_name' => $businessName, 'client_name' => $clientName, 'lead_id' => $leadId]
        );
    }

    public static function premiumExpiring($userId, $businessName, $daysLeft)
    {
        return static::send(
            userId: $userId,
            type: 'premium_expiring',
            title: 'Premium Subscription Expiring Soon ⏰',
            message: "Your premium subscription for {$businessName} expires in {$daysLeft} days. Renew now to keep premium features.",
            actionUrl: "/dashboard/subscription",
            extraData: ['business_name' => $businessName, 'days_left' => $daysLeft]
        );
    }

    public static function markAllAsReadForUser($userId)
    {
        return static::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public static function cleanupOldNotifications($days = 30)
    {
        return static::where('is_read', true)
            ->where('read_at', '<', now()->subDays($days))
            ->delete();
    }
}