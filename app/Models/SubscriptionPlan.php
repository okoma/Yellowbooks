<?php
// ============================================
// app/Models/SubscriptionPlan.php
// Free, Basic, Pro, Enterprise plans
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'yearly_price',
        'currency',
        'billing_interval',
        'trial_days',
        'features',
        'max_branches',
        'max_products',
        'max_team_members',
        'max_photos',
        'monthly_ad_credits',
        'is_popular',
        'is_active',
        'order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    // Relationships
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions()
    {
        return $this->hasMany(Subscription::class)->where('status', 'active');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    // Helper methods
    public function isFree()
    {
        return $this->price == 0;
    }

    public function hasFeature($feature)
    {
        if (!$this->features) {
            return false;
        }

        return isset($this->features[$feature]) && $this->features[$feature];
    }

    public function getMonthlyPrice()
    {
        if ($this->billing_interval === 'yearly' && $this->yearly_price) {
            return $this->yearly_price / 12;
        }

        return $this->price;
    }

    public function getSavingsPercentage()
    {
        if ($this->billing_interval !== 'yearly' || !$this->yearly_price) {
            return 0;
        }

        $monthlyTotal = $this->price * 12;
        $savings = $monthlyTotal - $this->yearly_price;

        return round(($savings / $monthlyTotal) * 100);
    }
}
