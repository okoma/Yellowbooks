<?php
// app/Models/BusinessInteraction.php - COMPLETE FIXED VERSION

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',           // For standalone businesses
        'business_branch_id',    // For multi-location businesses
        'user_id',
        'interaction_type',      // 'call', 'whatsapp', 'email', 'website', 'map', 'directions'
        'referral_source',
        'country',
        'country_code',
        'region',
        'city',
        'ip_address',
        'user_agent',
        'device_type',
        'interacted_at',
        'interaction_date',
        'interaction_hour',
        'interaction_month',
        'interaction_year',
    ];

    protected $casts = [
        'interacted_at' => 'datetime',
        'interaction_date' => 'date',
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

    /**
     * User who performed the interaction (optional - can be guest)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * Check if interaction is for standalone business
     */
    public function isForBusiness(): bool
    {
        return !is_null($this->business_id);
    }

    /**
     * Check if interaction is for branch
     */
    public function isForBranch(): bool
    {
        return !is_null($this->business_branch_id);
    }

    /**
     * Record an interaction (call, WhatsApp, email, etc.)
     * 
     * @param int|null $businessId Standalone business ID
     * @param int|null $branchId Branch ID
     * @param string $type Interaction type ('call', 'whatsapp', 'email', 'website', 'map', 'directions')
     * @param string $referralSource Source of traffic
     * @param int|null $userId User ID if logged in
     * @return static
     */
    public static function recordInteraction(
        $businessId = null, 
        $branchId = null, 
        $type, 
        $referralSource = 'direct', 
        $userId = null
    ) {
        // Validate: must have either business_id OR branch_id, not both
        if (!$businessId && !$branchId) {
            throw new \InvalidArgumentException('Must provide either businessId or branchId');
        }
        
        if ($businessId && $branchId) {
            throw new \InvalidArgumentException('Cannot provide both businessId and branchId');
        }

        // Validate interaction type
        $validTypes = ['call', 'whatsapp', 'email', 'website', 'map', 'directions'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid interaction type: {$type}");
        }

        $now = now();

        return static::create([
            'business_id' => $businessId,
            'business_branch_id' => $branchId,
            'user_id' => $userId,
            'interaction_type' => $type,
            'referral_source' => $referralSource,
            'country' => 'Unknown',
            'country_code' => null,
            'region' => 'Unknown',
            'city' => 'Unknown',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => static::detectDevice(),
            'interacted_at' => $now,
            'interaction_date' => $now->toDateString(),
            'interaction_hour' => $now->format('H'),
            'interaction_month' => $now->format('Y-m'),
            'interaction_year' => $now->format('Y'),
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
     * Scope for interactions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope for call interactions
     */
    public function scopeCalls($query)
    {
        return $query->where('interaction_type', 'call');
    }

    /**
     * Scope for WhatsApp interactions
     */
    public function scopeWhatsApp($query)
    {
        return $query->where('interaction_type', 'whatsapp');
    }

    /**
     * Scope for email interactions
     */
    public function scopeEmails($query)
    {
        return $query->where('interaction_type', 'email');
    }

    /**
     * Scope for website clicks
     */
    public function scopeWebsiteClicks($query)
    {
        return $query->where('interaction_type', 'website');
    }

    /**
     * Scope for map/directions clicks
     */
    public function scopeMapClicks($query)
    {
        return $query->whereIn('interaction_type', ['map', 'directions']);
    }

    /**
     * Scope for today's interactions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('interaction_date', today());
    }

    /**
     * Scope for this month's interactions
     */
    public function scopeThisMonth($query)
    {
        return $query->where('interaction_month', now()->format('Y-m'));
    }

    /**
     * Scope for interactions of standalone businesses only
     */
    public function scopeForBusinesses($query)
    {
        return $query->whereNotNull('business_id')->whereNull('business_branch_id');
    }

    /**
     * Scope for interactions of branches only
     */
    public function scopeForBranches($query)
    {
        return $query->whereNotNull('business_branch_id')->whereNull('business_id');
    }

    /**
     * Scope for interactions of a specific business
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for interactions of a specific branch
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('business_branch_id', $branchId);
    }

    /**
     * Scope for interactions by referral source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('referral_source', $source);
    }

    /**
     * Scope for interactions by device type
     */
    public function scopeByDevice($query, string $device)
    {
        return $query->where('device_type', $device);
    }

    /**
     * Scope for mobile interactions
     */
    public function scopeMobile($query)
    {
        return $query->where('device_type', 'mobile');
    }

    /**
     * Scope for desktop interactions
     */
    public function scopeDesktop($query)
    {
        return $query->where('device_type', 'desktop');
    }

    /**
     * Scope for interactions in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('interaction_date', [$startDate, $endDate]);
    }

    // ============================================
    // VALIDATION
    // ============================================

    /**
     * Boot method to ensure interaction belongs to either business OR branch
     */
    protected static function booted()
    {
        static::creating(function ($interaction) {
            // Ensure interaction belongs to either business OR branch, not both
            if ($interaction->business_id && $interaction->business_branch_id) {
                throw new \Exception('Interaction cannot belong to both business and branch. Choose one.');
            }

            // Ensure interaction belongs to at least one
            if (!$interaction->business_id && !$interaction->business_branch_id) {
                throw new \Exception('Interaction must belong to either a business or a branch.');
            }
        });
    }
}