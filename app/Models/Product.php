<?php
// ============================================
// app/Models/Product.php
// COMPLETE VERSION - Supports BOTH standalone businesses AND branches
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'business_id',           // NEW: For standalone businesses (direct relationship)
        'business_branch_id',    // For branches (nullable now)
        'header_title',          // Category/Section header
        'name',
        'slug',
        'description',
        'image',
        'currency',
        'price',
        'discount_type',         // 'none', 'percentage', 'fixed'
        'discount_value',
        'final_price',           // Auto-calculated
        'is_available',
        'order',                 // Display order
    ];
    protected $dates = ['deleted_at'];
    protected $casts = [
        'price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'final_price' => 'decimal:2',
        'is_available' => 'boolean',
        'order' => 'integer',
    ];

    protected $attributes = [
        'currency' => 'NGN',
        'discount_type' => 'none',
        'is_available' => true,
        'order' => 0,
    ];

    // ===== SLUG CONFIGURATION =====
    
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(255);
    }

    // ===== RELATIONSHIPS =====
    
    /**
     * Business (for standalone businesses WITHOUT branches)
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Branch (for multi-location businesses WITH branches)
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    // ===== HELPER METHODS =====

    /**
     * Get the parent business (either direct or through branch)
     * This always returns a Business model regardless of product type
     */
    public function getParentBusiness()
    {
        // If direct business relationship exists
        if ($this->business_id) {
            return $this->business;
        }
        
        // If branch relationship exists, get business through branch
        if ($this->business_branch_id && $this->branch) {
            return $this->branch->business;
        }
        
        return null;
    }

    /**
     * Get the location name (business or branch)
     */
    public function getLocationName(): ?string
    {
        if ($this->branch) {
            return $this->branch->branch_name;
        }
        
        if ($this->business) {
            return $this->business->business_name;
        }
        
        return null;
    }

    /**
     * Check if product belongs to a standalone business
     */
    public function isStandalone(): bool
    {
        return $this->business_id && !$this->business_branch_id;
    }

    /**
     * Check if product belongs to a branch
     */
    public function isBranchProduct(): bool
    {
        return $this->business_branch_id !== null;
    }

    /**
     * Check if product has a discount
     */
    public function hasDiscount(): bool
    {
        return $this->discount_type !== 'none' && $this->discount_value > 0;
    }

    /**
     * Get discount amount in currency
     */
    public function getDiscountAmount(): float
    {
        if (!$this->hasDiscount()) {
            return 0.0;
        }

        if ($this->discount_type === 'percentage') {
            return ($this->price * $this->discount_value) / 100;
        }

        return $this->discount_value;
    }

    /**
     * Get discount percentage (converts fixed to percentage)
     */
    public function getDiscountPercentage(): float
    {
        if (!$this->hasDiscount() || $this->price <= 0) {
            return 0.0;
        }

        if ($this->discount_type === 'percentage') {
            return $this->discount_value;
        }

        // Convert fixed amount to percentage
        return ($this->discount_value / $this->price) * 100;
    }

    /**
     * Get formatted price with currency symbol
     */
    public function getFormattedPrice(): string
    {
        return $this->formatMoney($this->price);
    }

    /**
     * Get formatted final price with currency symbol
     */
    public function getFormattedFinalPrice(): string
    {
        return $this->formatMoney($this->final_price);
    }

    /**
     * Format money based on currency
     */
    private function formatMoney($amount): string
    {
        $symbols = [
            'NGN' => '₦',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency;
        
        return $symbol . number_format($amount, 2);
    }

    // ===== SCOPES =====

/**
 * Scope for products with images
 */
public function scopeWithImages(Builder $query): Builder
{
    return $query->whereNotNull('image');
}

/**
 * Scope for products by price range
 */
public function scopePriceRange(Builder $query, $min, $max): Builder
{
    return $query->whereBetween('final_price', [$min, $max]);
}

/**
 * Scope for expensive products first
 */
public function scopeExpensiveFirst(Builder $query): Builder
{
    return $query->orderBy('final_price', 'desc');
}

/**
 * Scope for cheap products first
 */
public function scopeCheapFirst(Builder $query): Builder
{
    return $query->orderBy('final_price', 'asc');
}

/**
 * Scope for recently added products
 */
public function scopeRecent(Builder $query, int $days = 30): Builder
{
    return $query->where('created_at', '>=', now()->subDays($days));
}

/**
 * Scope for popular products (most viewed/ordered)
 * Note: You'll need to add view tracking for products first
 */
public function scopePopular(Builder $query, int $limit = 10): Builder
{
    return $query->orderBy('views_count', 'desc')->limit($limit);
}
    /**
     * Scope for available products
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope for unavailable products
     */
    public function scopeUnavailable(Builder $query): Builder
    {
        return $query->where('is_available', false);
    }

    /**
     * Scope for products with discounts
     */
    public function scopeWithDiscount(Builder $query): Builder
    {
        return $query->where('discount_type', '!=', 'none')
                     ->where('discount_value', '>', 0);
    }

    /**
     * Scope for products by header/category
     */
    public function scopeByHeader(Builder $query, string $header): Builder
    {
        return $query->where('header_title', $header);
    }

    /**
     * Scope for products by business
     */
    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for products by branch
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('business_branch_id', $branchId);
    }

    /**
     * Scope for standalone business products only
     */
    public function scopeStandalone(Builder $query): Builder
    {
        return $query->whereNotNull('business_id')
                     ->whereNull('business_branch_id');
    }

    /**
     * Scope for branch products only
     */
    public function scopeBranchOnly(Builder $query): Builder
    {
        return $query->whereNotNull('business_branch_id');
    }

    /**
     * Scope for ordering by custom order field
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order', 'asc')->orderBy('name', 'asc');
    }

    // ===== ACCESSORS =====

    /**
     * Get savings amount (original price - final price)
     */
    public function getSavingsAttribute(): float
    {
        return max(0, $this->price - $this->final_price);
    }

    /**
     * Get formatted savings
     */
    public function getFormattedSavingsAttribute(): string
    {
        return $this->formatMoney($this->savings);
    }

    // ===== MUTATORS & CALCULATIONS =====

    /**
     * Calculate final price based on discount
     */
    public function calculateFinalPrice(): void
    {
        // No discount
        if ($this->discount_type === 'none' || !$this->discount_value) {
            $this->final_price = $this->price;
            return;
        }

        // Percentage discount
        if ($this->discount_type === 'percentage') {
            $discount = ($this->price * $this->discount_value) / 100;
            $this->final_price = max(0, $this->price - $discount);
            return;
        }

        // Fixed amount discount
        if ($this->discount_type === 'fixed') {
            $this->final_price = max(0, $this->price - $this->discount_value);
            return;
        }

        // Fallback
        $this->final_price = $this->price;
    }

    // ===== BOOT METHOD =====

    protected static function booted(): void
    {
        // Auto-calculate final price before saving
        static::saving(function (Product $product) {
            $product->calculateFinalPrice();
        });

        // Ensure at least one parent is set
        static::creating(function (Product $product) {
            if (!$product->business_id && !$product->business_branch_id) {
                throw new \Exception('Product must belong to either a Business or a BusinessBranch');
            }
        });
    }
}
