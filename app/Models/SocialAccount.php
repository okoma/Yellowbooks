<?php
// ============================================
// app/Models/SocialAccount.php
// Business social media accounts (Business & Branch level)
// ============================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'platform',
        'url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ============================================
    // Relationships
    // ============================================
    
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function businessBranch()
    {
        return $this->belongsTo(BusinessBranch::class);
    }

    // ============================================
    // Scopes
    // ============================================
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    // NEW: Scope for business-level accounts only
    public function scopeBusinessLevel($query)
    {
        return $query->whereNull('business_branch_id');
    }

    // NEW: Scope for branch-level accounts only
    public function scopeBranchLevel($query)
    {
        return $query->whereNotNull('business_branch_id');
    }

    // ============================================
    // Helper Methods
    // ============================================
    // ✅ Add parent() helper
public function parent()
{
    return $this->business_id ? $this->business : $this->businessBranch;
}

// ✅ Add validation checks
public function isForBusiness(): bool
{
    return !is_null($this->business_id);
}

public function isForBranch(): bool
{
    return !is_null($this->business_branch_id);
}

// ✅ Add boot validation method
protected static function booted()
{
    static::creating(function ($socialAccount) {
        // Ensure belongs to either business OR branch, not both
        if ($socialAccount->business_id && $socialAccount->business_branch_id) {
            throw new \InvalidArgumentException(
                'SocialAccount cannot belong to both business and branch. Choose one.'
            );
        }

        // Ensure belongs to at least one
        if (!$socialAccount->business_id && !$socialAccount->business_branch_id) {
            throw new \InvalidArgumentException(
                'SocialAccount must belong to either a business or a branch.'
            );
        }
    });

    static::updating(function ($socialAccount) {
        // Prevent changing parent after creation
        if ($socialAccount->isDirty(['business_id', 'business_branch_id'])) {
            $original = $socialAccount->getOriginal();
            if (($original['business_id'] && $socialAccount->business_branch_id) || 
                ($original['business_branch_id'] && $socialAccount->business_id)) {
                throw new \InvalidArgumentException(
                    'Cannot change social account from business to branch or vice versa.'
                );
            }
        }
    });
}

// ✅ Add helpful scopes
public function scopeForBusiness($query, int $businessId)
{
    return $query->where('business_id', $businessId);
}

public function scopeForBranch($query, int $branchId)
{
    return $query->where('business_branch_id', $branchId);
}
  
  
    public function getPlatformIcon()
    {
        $icons = [
            'facebook' => 'fab fa-facebook',
            'instagram' => 'fab fa-instagram',
            'twitter' => 'fab fa-twitter',
            'linkedin' => 'fab fa-linkedin',
            'youtube' => 'fab fa-youtube',
            'tiktok' => 'fab fa-tiktok',
            'pinterest' => 'fab fa-pinterest',
            'whatsapp' => 'fab fa-whatsapp',
        ];
        
        return $icons[$this->platform] ?? 'fas fa-link';
    }

    // NEW: Check if this is a branch-specific account
    public function isBranchSpecific()
    {
        return !is_null($this->business_branch_id);
    }

    // NEW: Check if this is a business-level account
    public function isBusinessLevel()
    {
        return is_null($this->business_branch_id);
    }

    // NEW: Get the display name (Business or Branch name)
    public function getDisplayName()
    {
        if ($this->isBranchSpecific() && $this->businessBranch) {
            return $this->businessBranch->branch_title;
        }
        
        return $this->business->business_name ?? 'Unknown';
    }

    // NEW: Get platform display name
    public function getPlatformDisplayName()
    {
        $names = [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'twitter' => 'Twitter (X)',
            'linkedin' => 'LinkedIn',
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok',
            'pinterest' => 'Pinterest',
            'whatsapp' => 'WhatsApp Business',
        ];
        
        return $names[$this->platform] ?? ucfirst($this->platform);
    }
}
