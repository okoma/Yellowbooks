<?php

// ============================================
// app/Models/AdPackage.php
// Pre-defined ad campaign packages
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class AdPackage extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'campaign_type',
        'duration_days',
        'impressions_limit',
        'clicks_limit',
        'features',
        'is_popular',
        'is_active',
        'order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
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
    public function campaigns()
    {
        return $this->hasMany(AdCampaign::class);
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

    public function scopeByType($query, $type)
    {
        return $query->where('campaign_type', $type);
    }

    // Helper methods
    public function createCampaign($businessId, $userId, $customData = [])
    {
        $startDate = now();
        $endDate = $startDate->copy()->addDays($this->duration_days);

        return AdCampaign::create([
            'business_id' => $businessId,
            'purchased_by' => $userId,
            'ad_package_id' => $this->id,
            'type' => $this->campaign_type,
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'budget' => $this->price,
            'is_active' => true,
            'is_paid' => false,
            ...$customData
        ]);
    }

    public function getPricePerDay()
    {
        if ($this->duration_days == 0) return 0;
        return $this->price / $this->duration_days;
    }
}