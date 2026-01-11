<?php

// ============================================
// app/Models/PaymentMethod.php
// Cash, Card, Bank Transfer, etc.
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class PaymentMethod extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    // Relationships
    public function businesses()
    {
        return $this->belongsToMany(Business::class, 'business_payment_method');
    }
   public function branches()
  {
    return $this->belongsToMany(BusinessBranch::class, 'business_branch_payment_method');
  }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}