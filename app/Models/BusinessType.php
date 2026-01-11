<?php

// ============================================
// app/Models/BusinessType.php
// Restaurant, Hotel, Hospital, etc.
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class BusinessType extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'order',
        'is_active',
        'lead_form_fields',
        'lead_button_options',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lead_form_fields' => 'array',
        'lead_button_options' => 'array',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    // Relationships
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function businesses()
    {
        return $this->hasManyThrough(
            Business::class,
            Category::class,
            'business_type_id',
            'id',
            'id',
            'id'
        );
    }
}
