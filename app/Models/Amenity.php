<?php
// ============================================
// app/Models/Amenity.php
// WiFi, Parking, Wheelchair Access, etc.
// UPDATED: Added businesses relationship
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Amenity extends Model
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
    public function businesses() // NEW - for standalone businesses
    {
        return $this->belongsToMany(Business::class, 'business_amenity');
    }

    public function branches() // EXISTING - for business branches
    {
        return $this->belongsToMany(BusinessBranch::class, 'business_branch_amenity');
    }

    // Helper methods
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}