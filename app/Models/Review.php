<?php
// ============================================
// app/Models/Review.php
// Customer reviews - Polymorphic (can belong to Business OR BusinessBranch)
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Polymorphic fields (reviewable_type, reviewable_id handled automatically)
        'reviewable_type',      // 'App\Models\Business' or 'App\Models\BusinessBranch'
        'reviewable_id',        // ID of the business or branch
        
        // Review details
        'user_id',
        'rating',
        'comment',
        'photos',
        
        // Verification & Moderation
        'is_verified_purchase',
        'is_approved',
        'published_at',
        
        // Business Reply
        'reply',
        'replied_at',
        'replied_by',           // Optional: track who replied
    ];

    protected $casts = [
        'photos' => 'array',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'published_at' => 'datetime',
        'replied_at' => 'datetime',
        'rating' => 'integer',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Get the parent reviewable model (Business or BusinessBranch)
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The customer who wrote this review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Optional: User who replied to this review
     */
    public function repliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    // ===== HELPER METHODS =====

    /**
     * Check if this review is for a Business (not a branch)
     */
    public function isForBusiness(): bool
    {
        return $this->reviewable_type === Business::class;
    }

    /**
     * Check if this review is for a BusinessBranch
     */
    public function isForBranch(): bool
    {
        return $this->reviewable_type === BusinessBranch::class;
    }

    /**
     * Get the parent business (whether review is for business or branch)
     */
    public function getParentBusiness(): ?Business
    {
        if ($this->isForBusiness()) {
            return $this->reviewable;
        }
        
        if ($this->isForBranch()) {
            return $this->reviewable->business;
        }
        
        return null;
    }

    /**
     * Scope for approved reviews only
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for published reviews only
     */
    public function scopePublished($query)
    {
        return $query->where('is_approved', true)
                     ->whereNotNull('published_at');
    }

    /**
     * Scope for verified purchases only
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope for reviews with replies
     */
    public function scopeWithReply($query)
    {
        return $query->whereNotNull('reply');
    }

    /**
     * Scope for reviews by rating
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope for recent reviews
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ===== EVENTS =====

    protected static function booted()
    {
        // After creating a review
        static::created(function ($review) {
            if ($review->reviewable) {
                $review->reviewable->updateAggregateStats();
            }
        });

        // After updating a review
        static::updated(function ($review) {
            if ($review->reviewable) {
                $review->reviewable->updateAggregateStats();
            }
        });

        // After deleting a review
        static::deleted(function ($review) {
            if ($review->reviewable) {
                $review->reviewable->updateAggregateStats();
            }
        });

        // Auto-set published_at when approved
        static::updating(function ($review) {
            if ($review->isDirty('is_approved') && $review->is_approved && !$review->published_at) {
                $review->published_at = now();
            }
        });

        // Auto-set replied_at when reply is added
        static::updating(function ($review) {
            if ($review->isDirty('reply') && $review->reply && !$review->replied_at) {
                $review->replied_at = now();
            }
        });
    }

    // ===== ACCESSORS =====

    /**
     * Get star rating as emoji
     */
    public function getStarsAttribute(): string
    {
        return str_repeat('⭐', $this->rating);
    }

    /**
     * Get review status badge
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_approved) {
            return 'pending';
        }
        
        if ($this->is_approved && $this->published_at) {
            return 'published';
        }
        
        return 'approved';
    }
}