<?php
// app/Models/BusinessView.php - COMPLETE FIXED VERSION

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessView extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',           // For standalone businesses
        'business_branch_id',    // For multi-location businesses
        'referral_source',
        'country',
        'country_code',
        'region',
        'city',
        'ip_address',
        'user_agent',
        'device_type',
        'viewed_at',
        'view_date',
        'view_hour',
        'view_month',
        'view_year',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'view_date' => 'date',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * Business (for standalone businesses)
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Branch (for multi-location businesses)
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Get the parent (either Business or BusinessBranch)
     */
    public function parent()
    {
        return $this->business_id 
            ? $this->business 
            : $this->branch;
    }

    /**
     * Check if view is for standalone business
     */
    public function isForBusiness(): bool
    {
        return !is_null($this->business_id);
    }

    /**
     * Check if view is for branch
     */
    public function isForBranch(): bool
    {
        return !is_null($this->business_branch_id);
    }

    /**
     * Record a view for a business or branch
     * 
     * @param int|null $businessId Standalone business ID
     * @param int|null $branchId Branch ID
     * @param string $referralSource Source of traffic (e.g., 'yellowbooks', 'google', 'direct')
     * @return static
     */
    public static function recordView($businessId = null, $branchId = null, $referralSource = 'direct')
    {
        // Validate: must have either business_id OR branch_id, not both
        if (!$businessId && !$branchId) {
            throw new \InvalidArgumentException('Must provide either businessId or branchId');
        }
        
        if ($businessId && $branchId) {
            throw new \InvalidArgumentException('Cannot provide both businessId and branchId');
        }

        $now = now();

        return static::create([
            'business_id' => $businessId,
            'business_branch_id' => $branchId,
            'referral_source' => $referralSource,
            'country' => 'Unknown', // TODO: Integrate with IP geolocation service
            'country_code' => null,
            'region' => 'Unknown',
            'city' => 'Unknown',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => static::detectDevice(),
            'viewed_at' => $now,
            'view_date' => $now->toDateString(),
            'view_hour' => $now->format('H'),
            'view_month' => $now->format('Y-m'),
            'view_year' => $now->format('Y'),
        ]);
    }

    /**
     * Detect device type from user agent
     */
    private static function detectDevice(): string
    {
        $userAgent = request()->userAgent();
        
        if (preg_match('/mobile|android|iphone/i', $userAgent)) {
            return 'mobile';
        }
        
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope for views by referral source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('referral_source', $source);
    }

    /**
     * Scope for YellowBooks-originated views only
     */
    public function scopeYellowbooksOnly($query)
    {
        return $query->where('referral_source', 'yellowbooks');
    }

    /**
     * Scope for today's views
     */
    public function scopeToday($query)
    {
        return $query->whereDate('view_date', today());
    }

    /**
     * Scope for this month's views
     */
    public function scopeThisMonth($query)
    {
        return $query->where('view_month', now()->format('Y-m'));
    }

    /**
     * Scope for this year's views
     */
    public function scopeThisYear($query)
    {
        return $query->where('view_year', now()->format('Y'));
    }

    /**
     * Scope for views of standalone businesses only
     */
    public function scopeForBusinesses($query)
    {
        return $query->whereNotNull('business_id')->whereNull('business_branch_id');
    }

    /**
     * Scope for views of branches only
     */
    public function scopeForBranches($query)
    {
        return $query->whereNotNull('business_branch_id')->whereNull('business_id');
    }

    /**
     * Scope for views of a specific business
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for views of a specific branch
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('business_branch_id', $branchId);
    }

    /**
     * Scope for views by device type
     */
    public function scopeByDevice($query, string $device)
    {
        return $query->where('device_type', $device);
    }

    /**
     * Scope for mobile views
     */
    public function scopeMobile($query)
    {
        return $query->where('device_type', 'mobile');
    }

    /**
     * Scope for desktop views
     */
    public function scopeDesktop($query)
    {
        return $query->where('device_type', 'desktop');
    }

    /**
     * Scope for tablet views
     */
    public function scopeTablet($query)
    {
        return $query->where('device_type', 'tablet');
    }

    /**
     * Scope for views in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('view_date', [$startDate, $endDate]);
    }

    /**
     * Scope for views by country
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope for views by city
     */
    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    // ============================================
    // VALIDATION
    // ============================================

    /**
     * Boot method to ensure view belongs to either business OR branch
     */
    protected static function booted()
    {
        static::creating(function ($view) {
            // Ensure view belongs to either business OR branch, not both
            if ($view->business_id && $view->business_branch_id) {
                throw new \Exception('View cannot belong to both business and branch. Choose one.');
            }

            // Ensure view belongs to at least one
            if (!$view->business_id && !$view->business_branch_id) {
                throw new \Exception('View must belong to either a business or a branch.');
            }
        });
    }
}