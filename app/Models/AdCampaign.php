<?php
// ============================================
// app/Models/AdCampaign.php
// YellowBooks advertising campaigns
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'purchased_by',
        'ad_package_id',
        'transaction_id',
        'type',
        'title',
        'description',
        'banner_image',
        'target_locations',
        'target_categories',
        'starts_at',
        'ends_at',
        'budget',
        'is_active',
        'is_paid',
        'total_impressions',
        'total_clicks',
        'yellowbooks_impressions',
        'yellowbooks_clicks',
        'impressions_by_source',
        'clicks_by_source',
        'cost_per_impression',
        'cost_per_click',
        'total_spent',
        'ctr',
        'yellowbooks_ctr',
    ];

    protected $casts = [
        'target_locations' => 'array',
        'target_categories' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'budget' => 'decimal:2',
        'is_active' => 'boolean',
        'is_paid' => 'boolean',
        'impressions_by_source' => 'array',
        'clicks_by_source' => 'array',
        'cost_per_impression' => 'decimal:4',
        'cost_per_click' => 'decimal:4',
        'total_spent' => 'decimal:2',
        'ctr' => 'decimal:2',
        'yellowbooks_ctr' => 'decimal:2',
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function purchaser()
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function package()
    {
        return $this->belongsTo(AdPackage::class, 'ad_package_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopeExpiringSoon($query, $days = 3)
    {
        return $query->where('is_active', true)
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeBumpUp($query)
    {
        return $query->where('type', 'bump_up');
    }

    public function scopeSponsored($query)
    {
        return $query->where('type', 'sponsored');
    }

    public function scopeFeatured($query)
    {
        return $query->where('type', 'featured');
    }

    // Helper methods
    public function recordImpression($source = 'yellowbooks')
    {
        $this->increment('total_impressions');

        if ($source === 'yellowbooks') {
            $this->increment('yellowbooks_impressions');
            $this->calculateSpent();
        }

        // Update impressions by source
        $impressions = $this->impressions_by_source ?? [];
        $impressions[$source] = ($impressions[$source] ?? 0) + 1;
        $this->update(['impressions_by_source' => $impressions]);

        $this->calculateCTR();
    }

    public function recordClick($source = 'yellowbooks')
    {
        $this->increment('total_clicks');

        if ($source === 'yellowbooks') {
            $this->increment('yellowbooks_clicks');
            $this->calculateSpent();
        }

        // Update clicks by source
        $clicks = $this->clicks_by_source ?? [];
        $clicks[$source] = ($clicks[$source] ?? 0) + 1;
        $this->update(['clicks_by_source' => $clicks]);

        $this->calculateCTR();
    }

    private function calculateSpent()
    {
        $impressionCost = $this->yellowbooks_impressions * $this->cost_per_impression;
        $clickCost = $this->yellowbooks_clicks * $this->cost_per_click;

        $this->update([
            'total_spent' => $impressionCost + $clickCost
        ]);

        // Pause if budget exceeded
        if ($this->total_spent >= $this->budget) {
            $this->pause();
        }
    }

    private function calculateCTR()
    {
        // Overall CTR
        if ($this->total_impressions > 0) {
            $this->update([
                'ctr' => ($this->total_clicks / $this->total_impressions) * 100
            ]);
        }

        // YellowBooks CTR
        if ($this->yellowbooks_impressions > 0) {
            $this->update([
                'yellowbooks_ctr' => ($this->yellowbooks_clicks / $this->yellowbooks_impressions) * 100
            ]);
        }
    }

    public function isActive()
    {
        return $this->is_active 
            && $this->starts_at->isPast() 
            && $this->ends_at->isFuture()
            && $this->total_spent < $this->budget;
    }

    public function pause()
    {
        $this->update(['is_active' => false]);
    }

    public function resume()
    {
        if ($this->ends_at->isFuture() && $this->total_spent < $this->budget) {
            $this->update(['is_active' => true]);
        }
    }

    public function daysRemaining()
    {
        return max(0, now()->diffInDays($this->ends_at, false));
    }

    public function budgetRemaining()
    {
        return max(0, $this->budget - $this->total_spent);
    }

    public function budgetUsedPercentage()
    {
        if ($this->budget == 0) return 0;
        return min(100, ($this->total_spent / $this->budget) * 100);
    }
}
