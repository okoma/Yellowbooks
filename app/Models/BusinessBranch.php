<?php

// ============================================
// app/Models/BusinessBranch.php - UPDATED VERSION
// Add payment methods and manager relationships
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Laravel\Scout\Searchable;

class BusinessBranch extends Model
{
    use HasFactory, HasSlug, Searchable;

    protected $fillable = [
        'business_id',
        'branch_title',
        'slug',
        'canonical_strategy',
        'canonical_url',
        'branch_description',
        'meta_title',
        'meta_description',
        'unique_features',
        'nearby_landmarks',
        'has_unique_content',
        'content_similarity_score',
        'is_main_branch',
        'address',
        'city',
        'area',
        'state',
        'latitude',
        'longitude',
        'phone',
        'email',
        'whatsapp',
        'business_hours',
        'gallery',
        'rating',
        'reviews_count',
        'views_count',
        'leads_count',
        'saves_count',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_main_branch' => 'boolean',
        'business_hours' => 'array',
        'gallery' => 'array',
        'unique_features' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'rating' => 'decimal:2',
        'content_similarity_score' => 'decimal:2',
        'is_active' => 'boolean',
        'has_unique_content' => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(function ($model) {
                return $model->business->business_name . ' ' . $model->city;
            })
            ->saveSlugsTo('slug');
    }

    public function toSearchableArray()
    {
        return [
            'branch_title' => $this->branch_title,
            'address' => $this->address,
            'city' => $this->city,
            'area' => $this->area,
            'state' => $this->state,
            'business_name' => $this->business->business_name,
        ];
    }

    // ============================================
    // Core Relationships
    // ============================================

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'business_branch_location');
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'business_branch_amenity');
    }

    // NEW: Payment Methods Relationship
    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'business_branch_payment_method');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Branch Reviews (Polymorphic)
     * Reviews attached to this specific branch
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function views()
    {
        return $this->hasMany(BusinessView::class);
    }

    public function interactions()
    {
        return $this->hasMany(BusinessInteraction::class);
    }

    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'saved_businesses', 'business_branch_id', 'user_id')
            ->withTimestamps();
    }

    // ============================================
    // Manager Relationships
    // ============================================

    public function managers()
    {
        return $this->hasMany(BranchManager::class);
    }

    public function activeManagers()
    {
        return $this->hasMany(BranchManager::class)->where('is_active', true);
    }

    public function primaryManager()
    {
        return $this->hasOne(BranchManager::class)
            ->where('is_active', true)
            ->where('is_primary', true);
    }

    public function managerInvitations()
    {
        return $this->hasMany(ManagerInvitation::class);
    }

    public function pendingManagerInvitations()
    {
        return $this->hasMany(ManagerInvitation::class)
            ->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function managerActivityLogs()
    {
        return $this->hasMany(ManagerActivityLog::class);
    }

    // ============================================
    // Manager Helper Methods
    // ============================================

    public function hasManagers()
    {
        return $this->activeManagers()->exists();
    }

    public function getManagersCount()
    {
        return $this->activeManagers()->count();
    }

    public function isManagerActive($userId)
    {
        return $this->activeManagers()
            ->where('user_id', $userId)
            ->exists();
    }

    public function getManagerPermissions($userId)
    {
        $manager = $this->activeManagers()
            ->where('user_id', $userId)
            ->first();

        return $manager ? $manager->permissions : [];
    }

    public function assignManager($userId, $assignedBy, $position = 'Branch Manager', $permissions = [], $isPrimary = false)
    {
        return BranchManager::create([
            'business_branch_id' => $this->id,
            'user_id' => $userId,
            'position' => $position,
            'permissions' => $permissions,
            'is_primary' => $isPrimary,
            'assigned_by' => $assignedBy,
            'assigned_at' => now(),
            'is_active' => true,
        ]);
    }

    public function removeManager($userId)
    {
        $this->managers()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'removed_at' => now(),
            ]);
    }
// ============================================
// Officials & Social Media (Branch-Specific)
// ============================================

/**
 * Branch Officials/Staff Members
 * These are the employees working at this specific branch
 */
public function officials()
{
    return $this->hasMany(Official::class);
}

/**
 * Get active officials only
 */
public function activeOfficials()
{
    return $this->hasMany(Official::class)->where('is_active', true);
}

/**
 * Social Media Accounts for this branch
 * Each branch can have its own social media presence
 */
public function socialAccounts()
{
    return $this->hasMany(SocialAccount::class);
}

/**
 * Get active social accounts only
 */
public function activeSocialAccounts()
{
    return $this->hasMany(SocialAccount::class)->where('is_active', true);
}
  
    // ============================================
    // Business Hours & Status Helper Methods
    // ============================================

    public function isOpen()
    {
        if (!$this->business_hours) {
            return null;
        }

        $day = strtolower(now()->format('l'));
        $currentTime = now()->format('H:i');

        if (!isset($this->business_hours[$day])) {
            return false;
        }

        $hours = $this->business_hours[$day];
        
        if ($hours['closed'] ?? false) {
            return false;
        }

        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    public function updateRating()
    {
        $this->update([
            'rating' => $this->reviews()->avg('rating') ?? 0,
            'reviews_count' => $this->reviews()->count(),
        ]);

        $this->business->updateAggregateStats();
    }

    public function getFullAddressAttribute()
    {
        return "{$this->address}, {$this->city}, {$this->state}";
    }

    // ============================================
    // SEO & Content Uniqueness Methods
    // ============================================

    /**
     * Calculate content similarity between branch and parent business
     */
    public function calculateContentSimilarity()
    {
        if (!$this->business || !$this->branch_description || !$this->business->description) {
            return 0;
        }

        similar_text(
            strtolower($this->branch_description),
            strtolower($this->business->description),
            $percent
        );

        $this->update([
            'content_similarity_score' => round($percent, 2),
            'has_unique_content' => $percent < 30, // Less than 30% similar = unique
        ]);

        return round($percent, 2);
    }

    /**
     * Check if content is too similar to parent
     */
    public function hasSimilarContentToParent()
    {
        if (!$this->content_similarity_score) {
            $this->calculateContentSimilarity();
        }

        return $this->content_similarity_score > 70; // More than 70% similar = warning
    }

    /**
     * Check if branch has sufficient unique content
     */
    public function hasUniqueContent()
    {
        return $this->has_unique_content;
    }

    /**
     * Get the canonical URL for this branch
     */
    public function getCanonicalUrl()
    {
        // If custom canonical URL is set, use it
        if ($this->canonical_url) {
            return $this->canonical_url;
        }

        // If strategy is 'parent', return parent business URL
        if ($this->canonical_strategy === 'parent' && $this->business) {
            return route('business.show', $this->business->slug);
        }

        // Default: self-referencing (index separately)
        return route('branch.show', $this->slug);
    }

    /**
     * Get meta title (auto-generate if not set)
     */
    public function getMetaTitleAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Auto-generate: "Branch Title | Business Name | City"
        return "{$this->branch_title} | {$this->city}, {$this->state}";
    }

    /**
     * Get meta description (auto-generate if not set)
     */
    public function getMetaDescriptionAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Auto-generate from branch description
        return \Illuminate\Support\Str::limit($this->branch_description, 155);
    }

    /**
     * Generate Schema.org JSON-LD markup for branch
     */
    public function getSchemaMarkup()
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $this->business->businessType->schema_type ?? 'LocalBusiness',
            'name' => $this->branch_title,
            'description' => $this->branch_description,
            'url' => route('branch.show', $this->slug),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->address,
                'addressLocality' => $this->city,
                'addressRegion' => $this->state,
                'addressCountry' => 'NG',
            ],
        ];

        // Add parent business reference
        if ($this->business) {
            $schema['branchOf'] = [
                '@type' => $this->business->businessType->schema_type ?? 'Organization',
                'name' => $this->business->business_name,
                '@id' => route('business.show', $this->business->slug),
            ];
        }

        // Add contact info
        if ($this->phone) {
            $schema['telephone'] = $this->phone;
        }

        if ($this->email) {
            $schema['email'] = $this->email;
        }

        // Add geo coordinates
        if ($this->latitude && $this->longitude) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ];
        }

        // Add rating
        if ($this->rating && $this->reviews_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $this->rating,
                'reviewCount' => $this->reviews_count,
            ];
        }

        // Add opening hours
        if ($this->business_hours) {
            $openingHours = [];
            foreach ($this->business_hours as $day => $hours) {
                if (!($hours['closed'] ?? false) && isset($hours['open'], $hours['close'])) {
                    $dayAbbr = ucfirst(substr($day, 0, 2));
                    $openingHours[] = "{$dayAbbr} {$hours['open']}-{$hours['close']}";
                }
            }
            if (!empty($openingHours)) {
                $schema['openingHours'] = $openingHours;
            }
        }

        return $schema;
    }

    /**
     * Get content quality score (0-100)
     */
    public function getContentQualityScore()
    {
        $score = 0;

        // Has description (20 points)
        if ($this->branch_description && strlen($this->branch_description) >= 100) {
            $score += 20;
        } elseif ($this->branch_description) {
            $score += 10;
        }

        // Has unique content (30 points)
        if ($this->has_unique_content) {
            $score += 30;
        } elseif ($this->content_similarity_score && $this->content_similarity_score < 50) {
            $score += 15;
        }

        // Has photos (10 points)
        if ($this->gallery && count($this->gallery) >= 3) {
            $score += 10;
        } elseif ($this->gallery && count($this->gallery) > 0) {
            $score += 5;
        }

        // Has reviews (20 points)
        if ($this->reviews_count >= 5) {
            $score += 20;
        } elseif ($this->reviews_count > 0) {
            $score += 10;
        }

        // Has unique features (10 points)
        if ($this->unique_features && count($this->unique_features) >= 3) {
            $score += 10;
        } elseif ($this->unique_features && count($this->unique_features) > 0) {
            $score += 5;
        }

        // Has nearby landmarks (10 points)
        if ($this->nearby_landmarks && strlen($this->nearby_landmarks) >= 50) {
            $score += 10;
        } elseif ($this->nearby_landmarks) {
            $score += 5;
        }

        return $score;
    }
}