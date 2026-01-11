<?php
// app/Models/BusinessViewSummary.php - COMPLETE FIXED VERSION

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessViewSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',           // For standalone businesses
        'business_branch_id',    // For multi-location businesses
        'period_type',           // 'hourly', 'daily', 'monthly', 'yearly'
        'period_key',            // e.g., '2024-01-15', '2024-01', '2024'
        'total_views',
        'views_by_source',       // JSON: {'yellowbooks': 100, 'google': 50, ...}
        'views_by_country',      // JSON: {'NG': 200, 'US': 50, ...}
        'views_by_device',       // JSON: {'mobile': 150, 'desktop': 100, 'tablet': 50}
        'total_calls',
        'total_whatsapp',
        'total_emails',
        'total_website_clicks',
        'total_map_clicks',
    ];

    protected $casts = [
        'views_by_source' => 'array',
        'views_by_country' => 'array',
        'views_by_device' => 'array',
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
        return $this->business_id ? $this->business : $this->branch;
    }

    /**
     * Check if summary is for standalone business
     */
    public function isForBusiness(): bool
    {
        return !is_null($this->business_id);
    }

    /**
     * Check if summary is for branch
     */
    public function isForBranch(): bool
    {
        return !is_null($this->business_branch_id);
    }

    /**
     * Aggregate statistics for a business or branch
     * 
     * @param int|null $businessId Standalone business ID
     * @param int|null $branchId Branch ID
     * @param string $periodType 'hourly', 'daily', 'monthly', 'yearly'
     * @param string $periodKey e.g., '2024-01-15', '2024-01', '2024'
     * @return static
     */
    public static function aggregateFor($businessId = null, $branchId = null, $periodType, $periodKey)
    {
        if (!$businessId && !$branchId) {
            throw new \InvalidArgumentException('Must provide either businessId or branchId');
        }
        
        if ($businessId && $branchId) {
            throw new \InvalidArgumentException('Cannot provide both businessId and branchId');
        }

        // Build base query for views
        $viewsQuery = BusinessView::query();
        if ($businessId) {
            $viewsQuery->where('business_id', $businessId);
        } else {
            $viewsQuery->where('business_branch_id', $branchId);
        }

        // Apply period filter
        $viewsQuery = static::applyPeriodFilter($viewsQuery, $periodType, $periodKey);
        $views = $viewsQuery->get();

        // Build base query for interactions
        $interactionsQuery = BusinessInteraction::query();
        if ($businessId) {
            $interactionsQuery->where('business_id', $businessId);
        } else {
            $interactionsQuery->where('business_branch_id', $branchId);
        }

        // Apply period filter
        $interactionsQuery = static::applyPeriodFilter(
            $interactionsQuery, 
            $periodType, 
            $periodKey, 
            'interaction_date', 
            'interaction_hour', 
            'interaction_month', 
            'interaction_year'
        );
        $interactions = $interactionsQuery->get();

        return static::updateOrCreate(
            [
                'business_id' => $businessId,
                'business_branch_id' => $branchId,
                'period_type' => $periodType,
                'period_key' => $periodKey,
            ],
            [
                'total_views' => $views->count(),
                'views_by_source' => $views->groupBy('referral_source')->map->count()->toArray(),
                'views_by_country' => $views->groupBy('country')->map->count()->toArray(),
                'views_by_device' => $views->groupBy('device_type')->map->count()->toArray(),
                'total_calls' => $interactions->where('interaction_type', 'call')->count(),
                'total_whatsapp' => $interactions->where('interaction_type', 'whatsapp')->count(),
                'total_emails' => $interactions->where('interaction_type', 'email')->count(),
                'total_website_clicks' => $interactions->where('interaction_type', 'website')->count(),
                'total_map_clicks' => $interactions->whereIn('interaction_type', ['map', 'directions'])->count(),
            ]
        );
    }

    /**
     * Apply period filter to query
     */
    private static function applyPeriodFilter(
        $query, 
        $periodType, 
        $periodKey, 
        $dateField = 'view_date', 
        $hourField = 'view_hour', 
        $monthField = 'view_month', 
        $yearField = 'view_year'
    ) {
        return $query->when($periodType === 'hourly', function($q) use ($periodKey, $dateField, $hourField) {
                $q->where($dateField, substr($periodKey, 0, 10))
                  ->where($hourField, substr($periodKey, -2));
            })
            ->when($periodType === 'daily', function($q) use ($periodKey, $dateField) {
                $q->where($dateField, $periodKey);
            })
            ->when($periodType === 'monthly', function($q) use ($periodKey, $monthField) {
                $q->where($monthField, $periodKey);
            })
            ->when($periodType === 'yearly', function($q) use ($periodKey, $yearField) {
                $q->where($yearField, $periodKey);
            });
    }

    /**
     * Get total interactions
     */
    public function getTotalInteractions(): int
    {
        return $this->total_calls 
            + $this->total_whatsapp 
            + $this->total_emails 
            + $this->total_website_clicks 
            + $this->total_map_clicks;
    }

    /**
     * Get conversion rate (interactions / views)
     */
    public function getConversionRate(): float
    {
        if ($this->total_views === 0) {
            return 0;
        }
        
        return round(($this->getTotalInteractions() / $this->total_views) * 100, 2);
    }

    /**
     * Get top referral source
     */
    public function getTopReferralSource(): ?string
    {
        if (empty($this->views_by_source)) {
            return null;
        }
        
        return array_key_first(
            array_slice(
                arsort($this->views_by_source) ? $this->views_by_source : [], 
                0, 
                1, 
                true
            )
        );
    }

    /**
     * Get top device type
     */
    public function getTopDevice(): ?string
    {
        if (empty($this->views_by_device)) {
            return null;
        }
        
        $sorted = $this->views_by_device;
        arsort($sorted);
        return array_key_first($sorted);
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope for summaries of standalone businesses only
     */
    public function scopeForBusinesses($query)
    {
        return $query->whereNotNull('business_id')->whereNull('business_branch_id');
    }

    /**
     * Scope for summaries of branches only
     */
    public function scopeForBranches($query)
    {
        return $query->whereNotNull('business_branch_id')->whereNull('business_id');
    }

    /**
     * Scope for summaries of a specific business
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for summaries of a specific branch
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('business_branch_id', $branchId);
    }

    /**
     * Scope by period type
     */
    public function scopeByPeriodType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope for hourly summaries
     */
    public function scopeHourly($query)
    {
        return $query->where('period_type', 'hourly');
    }

    /**
     * Scope for daily summaries
     */
    public function scopeDaily($query)
    {
        return $query->where('period_type', 'daily');
    }

    /**
     * Scope for monthly summaries
     */
    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    /**
     * Scope for yearly summaries
     */
    public function scopeYearly($query)
    {
        return $query->where('period_type', 'yearly');
    }

    /**
     * Scope for specific period key
     */
    public function scopeForPeriod($query, string $periodKey)
    {
        return $query->where('period_key', $periodKey);
    }

    /**
     * Scope for period range
     */
    public function scopePeriodRange($query, string $startKey, string $endKey)
    {
        return $query->whereBetween('period_key', [$startKey, $endKey]);
    }

    // ============================================
    // VALIDATION
    // ============================================

    /**
     * Boot method to ensure summary belongs to either business OR branch
     */
    protected static function booted()
    {
        static::creating(function ($summary) {
            if ($summary->business_id && $summary->business_branch_id) {
                throw new \Exception('BusinessViewSummary cannot belong to both business and branch. Choose one.');
            }

            if (!$summary->business_id && !$summary->business_branch_id) {
                throw new \Exception('BusinessViewSummary must belong to either a business or a branch.');
            }
        });
    }
}