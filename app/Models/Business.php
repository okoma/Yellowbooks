<?php
// app/Models/Business.php - COMPLETE FIXED VERSION

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Business extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    protected $fillable = [
        'user_id',
        'business_type_id',
        'business_name',
        'slug',
        'description',
        'logo',
        'cover_photo',
        'gallery',
        
        // Contact Information
        'email',
        'phone',
        'whatsapp',
        'website',
        'whatsapp_message',
        
        // Location
        'state_location_id',
        'city_location_id',
        'state',
        'city',
        'area',
        'address',
        'latitude',
        'longitude',
        
        // Legal Information
        'registration_number',
        'entity_type',
        'years_in_business',
        
        // Business Hours
        'business_hours',
        
        // Verification & Status
        'is_claimed',
        'claimed_by',
        'is_verified',
        'verification_level',
        'verification_score',
        'is_premium',
        'premium_until',
        'status',
        
        // Aggregated Stats
        'avg_rating',
        'total_reviews',
        'total_views',
        'total_leads',
        'total_saves',
    ];

    protected $casts = [
        'gallery' => 'array',
        'business_hours' => 'array',
        'is_claimed' => 'boolean',
        'is_verified' => 'boolean',
        'is_premium' => 'boolean',
        'premium_until' => 'datetime',
        'verification_score' => 'integer',
        'avg_rating' => 'float',
        'total_reviews' => 'integer',
        'total_views' => 'integer',
        'total_leads' => 'integer',
        'total_saves' => 'integer',
    ];

    // ============================================
    // SLUG CONFIGURATION
    // ============================================
    
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('business_name')
            ->saveSlugsTo('slug');
    }

    // ============================================
    // CORE RELATIONSHIPS
    // ============================================
    
    /**
     * Business Owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * User who claimed this business
     */
    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    /**
     * Business Type (e.g., Restaurant, Hotel, etc.)
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * Business Categories
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'business_category');
    }

    /**
     * State Location
     */
    public function stateLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'state_location_id');
    }

    /**
     * City Location
     */
    public function cityLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'city_location_id');
    }

    // ============================================
    // DIRECT RELATIONSHIPS (For Standalone Businesses)
    // ============================================
    
    /**
     * Products/Services (for standalone businesses WITHOUT branches)
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Customer Leads (for standalone businesses WITHOUT branches)
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Business Officials (for standalone businesses WITHOUT branches)
     */
    public function officials(): HasMany
    {
        return $this->hasMany(Official::class);
    }

    /**
     * Social Media Accounts
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    // ============================================
    // VIEWS, INTERACTIONS & ANALYTICS
    // ============================================

    /**
     * Business Views (for standalone businesses)
     */
    public function views(): HasMany
    {
        return $this->hasMany(BusinessView::class);
    }

    /**
     * Customer Interactions (calls, WhatsApp, email, website, map)
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(BusinessInteraction::class);
    }

    /**
     * View Summaries (aggregated analytics)
     */
    public function viewSummaries(): HasMany
    {
        return $this->hasMany(BusinessViewSummary::class);
    }

    /**
     * Users who saved/bookmarked this business
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_businesses', 'business_id', 'user_id')
            ->withTimestamps();
    }

    // ============================================
    // MULTI-LOCATION RELATIONSHIPS
    // ============================================
    
    /**
     * Business Branches (for multi-location businesses)
     */
    public function branches(): HasMany
    {
        return $this->hasMany(BusinessBranch::class);
    }

    // ============================================
    // FEATURES & AMENITIES
    // ============================================
    
    /**
     * Payment Methods
     */
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class, 'business_payment_method');
    }

    /**
     * Business Amenities
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'business_amenity');
    }

    // ============================================
    // REVIEWS & RATINGS
    // ============================================
    
    /**
     * Business Reviews (Polymorphic)
     * Reviews can be attached to Business (standalone) or BusinessBranch (multi-location)
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Check if this is a standalone business (no branches)
     */
    public function isStandalone(): bool
    {
        return $this->branches()->count() === 0;
    }

    /**
     * Check if this is a multi-location business (has branches)
     */
    public function isMultiLocation(): bool
    {
        return $this->branches()->count() > 0;
    }

    /**
     * Get all products (direct products + products from all branches)
     */
    public function allProducts()
    {
        // Direct products (for standalone businesses)
        $directProducts = $this->products;
        
        // Products from all branches (for multi-location businesses)
        $branchProducts = $this->branches()
            ->with('products')
            ->get()
            ->pluck('products')
            ->flatten();
        
        return $directProducts->merge($branchProducts);
    }

    /**
     * Get all leads (direct leads + leads from all branches)
     */
    public function allLeads()
    {
        // Direct leads (for standalone businesses)
        $directLeads = $this->leads;
        
        // Leads from all branches (for multi-location businesses)
        $branchLeads = $this->branches()
            ->with('leads')
            ->get()
            ->pluck('leads')
            ->flatten();
        
        return $directLeads->merge($branchLeads);
    }

    /**
     * Get all officials (direct officials + officials from all branches)
     */
    public function allOfficials()
    {
        // Direct officials (for standalone businesses)
        $directOfficials = $this->officials;
        
        // Officials from all branches (for multi-location businesses)
        $branchOfficials = $this->branches()
            ->with('officials')
            ->get()
            ->pluck('officials')
            ->flatten();
        
        return $directOfficials->merge($branchOfficials);
    }

    /**
     * Get all reviews (direct reviews + reviews from all branches)
     */
    public function allReviews()
    {
        // Direct reviews (for standalone businesses)
        $directReviews = $this->reviews;
        
        // Reviews from all branches (for multi-location businesses)
        $branchReviews = $this->branches()
            ->with('reviews')
            ->get()
            ->pluck('reviews')
            ->flatten();
        
        return $directReviews->merge($branchReviews);
    }

    /**
     * Get total product count (including branches)
     */
    public function getTotalProductsCount(): int
    {
        $directCount = $this->products()->count();
        $branchCount = $this->branches()->withCount('products')->get()->sum('products_count');
        
        return $directCount + $branchCount;
    }

    /**
     * Get total leads count (including branches)
     */
    public function getTotalLeadsCount(): int
    {
        $directCount = $this->leads()->count();
        $branchCount = $this->branches()->withCount('leads')->get()->sum('leads_count');
        
        return $directCount + $branchCount;
    }

    /**
     * Get total officials count (including branches)
     */
    public function getTotalOfficialsCount(): int
    {
        $directCount = $this->officials()->count();
        $branchCount = $this->branches()->withCount('officials')->get()->sum('officials_count');
        
        return $directCount + $branchCount;
    }

    /**
     * Get total reviews count (including branches)
     */
    public function getTotalReviewsCount(): int
    {
        $directCount = $this->reviews()->count();
        $branchCount = $this->branches()->withCount('reviews')->get()->sum('reviews_count');
        
        return $directCount + $branchCount;
    }

    // ============================================
    // ANALYTICS HELPER METHODS
    // ============================================

    /**
     * Get total views count (direct + all branches)
     */
    public function getTotalViewsCount(): int
    {
        // Direct views (standalone)
        $directViews = $this->views()->count();
        
        // Branch views (multi-location)
        $branchViews = $this->branches()->withCount('views')->get()->sum('views_count');
        
        return $directViews + $branchViews;
    }

    /**
     * Get total interactions count (direct + all branches)
     */
    public function getTotalInteractionsCount(): int
    {
        $directInteractions = $this->interactions()->count();
        $branchInteractions = $this->branches()->withCount('interactions')->get()->sum('interactions_count');
        
        return $directInteractions + $branchInteractions;
    }

    /**
     * Get saves/bookmarks count (direct + all branches)
     */
    public function getTotalSavesCount(): int
    {
        $directSaves = $this->savedByUsers()->count();
        $branchSaves = $this->branches()->withCount('savedByUsers')->get()->sum('saved_by_users_count');
        
        return $directSaves + $branchSaves;
    }

    /**
     * Get interaction breakdown by type (calls, whatsapp, email, etc.)
     */
    public function getInteractionBreakdown(): array
    {
        // Direct interactions
        $interactions = $this->interactions()
            ->selectRaw('interaction_type, COUNT(*) as count')
            ->groupBy('interaction_type')
            ->pluck('count', 'interaction_type')
            ->toArray();
        
        // Branch interactions
        $branchInteractions = BusinessInteraction::whereIn(
            'business_branch_id', 
            $this->branches()->pluck('id')
        )
        ->selectRaw('interaction_type, COUNT(*) as count')
        ->groupBy('interaction_type')
        ->pluck('count', 'interaction_type')
        ->toArray();
        
        // Merge counts
        foreach ($branchInteractions as $type => $count) {
            $interactions[$type] = ($interactions[$type] ?? 0) + $count;
        }
        
        return $interactions;
    }

    /**
     * Get views by referral source
     */
    public function getViewsBySource(): array
    {
        // Direct views
        $views = $this->views()
            ->selectRaw('referral_source, COUNT(*) as count')
            ->groupBy('referral_source')
            ->pluck('count', 'referral_source')
            ->toArray();
        
        // Branch views
        $branchViews = BusinessView::whereIn(
            'business_branch_id', 
            $this->branches()->pluck('id')
        )
        ->selectRaw('referral_source, COUNT(*) as count')
        ->groupBy('referral_source')
        ->pluck('count', 'referral_source')
        ->toArray();
        
        // Merge counts
        foreach ($branchViews as $source => $count) {
            $views[$source] = ($views[$source] ?? 0) + $count;
        }
        
        return $views;
    }

    /**
     * Check if business has any analytics data
     */
    public function hasAnalyticsData(): bool
    {
        return $this->views()->exists() 
            || $this->interactions()->exists()
            || $this->branches()->whereHas('views')->exists();
    }

    /**
     * Record a page view for standalone business
     */
    public function recordView(string $referralSource = 'direct')
    {
        return BusinessView::recordView(
            businessId: $this->id,
            referralSource: $referralSource
        );
    }

    /**
     * Record an interaction for standalone business
     */
    public function recordInteraction(string $type, string $referralSource = 'direct', ?int $userId = null)
    {
        return BusinessInteraction::recordInteraction(
            businessId: $this->id,
            type: $type,
            referralSource: $referralSource,
            userId: $userId
        );
    }

    /**
     * Get average rating across all locations
     */
    public function getOverallRating(): float
    {
        // If standalone, just use business reviews
        if ($this->isStandalone()) {
            return round($this->reviews()->avg('rating') ?? 0, 2);
        }
        
        // If multi-location, aggregate from all branches
        $branchAvg = $this->branches()
            ->withAvg('reviews', 'rating')
            ->get()
            ->avg('reviews_avg_rating');
        
        return round($branchAvg ?? 0, 2);
    }

    /**
     * Update aggregate statistics for this business
     * Called after reviews are added/updated or leads are created
     */
    public function updateAggregateStats()
    {
        $this->update([
            'avg_rating' => $this->getOverallRating(),
            'total_reviews' => $this->getTotalReviewsCount(),
            'total_leads' => $this->getTotalLeadsCount(),
            'total_views' => $this->getTotalViewsCount(),
            'total_saves' => $this->getTotalSavesCount(),
        ]);
        
        return $this;
    }

    // ============================================
    // STATUS CHECKS
    // ============================================

    /**
     * Check if business is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if business is verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified === true;
    }

    /**
     * Check if business is premium
     */
    public function isPremium(): bool
    {
        return $this->is_premium === true && 
               ($this->premium_until === null || $this->premium_until->isFuture());
    }

    /**
     * Check if business is claimed
     */
    public function isClaimed(): bool
    {
        return $this->is_claimed === true;
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope for active businesses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for verified businesses
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for premium businesses
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true)
                     ->where(function ($q) {
                         $q->whereNull('premium_until')
                           ->orWhere('premium_until', '>', now());
                     });
    }

    /**
     * Scope for standalone businesses (no branches)
     */
    public function scopeStandalone($query)
    {
        return $query->doesntHave('branches');
    }

    /**
     * Scope for multi-location businesses (has branches)
     */
    public function scopeMultiLocation($query)
    {
        return $query->has('branches');
    }

    /**
     * Scope for businesses by type
     */
    public function scopeByType($query, $businessTypeId)
    {
        return $query->where('business_type_id', $businessTypeId);
    }

    /**
     * Scope for businesses by location
     */
    public function scopeByLocation($query, $stateId = null, $cityId = null)
    {
        if ($stateId) {
            $query->where('state_location_id', $stateId);
        }
        
        if ($cityId) {
            $query->where('city_location_id', $cityId);
        }
        
        return $query;
    }

    /**
     * Scope for claimed businesses
     */
    public function scopeClaimed($query)
    {
        return $query->where('is_claimed', true);
    }

    /**
     * Scope for unclaimed businesses
     */
    public function scopeUnclaimed($query)
    {
        return $query->where('is_claimed', false);
    }

    // ============================================
    // ACCESSORS & MUTATORS
    // ============================================

    /**
     * Get the full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->area,
            $this->city,
            $this->state,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get the business age in years
     */
    public function getAgeAttribute(): ?int
    {
        return $this->years_in_business;
    }

    // ============================================
    // BOOT METHOD
    // ============================================

    protected static function booted()
    {
        // Auto-generate slug on creation if not provided
        static::creating(function ($business) {
            if (empty($business->slug)) {
                $business->slug = \Illuminate\Support\Str::slug($business->business_name);
            }
        });
    }
}