<?php
// ============================================
// app/Models/Official.php
// Business team members/staff with social accounts
// Supports both standalone businesses AND branches
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Official extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',           // For standalone businesses
        'business_branch_id',    // For multi-location businesses (branches)
        'name',
        'position',
        'photo',
        'social_accounts',       // JSON: {"linkedin": "url", "twitter": "url", etc}
        'is_active',
        'order',
    ];

    protected $casts = [
        'social_accounts' => 'array',
        'is_active' => 'boolean',
    ];

    // ============================================
    // Relationships
    // ============================================

    /**
     * Official belongs to a Business (for standalone businesses)
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Official belongs to a BusinessBranch (for multi-location businesses)
     */
    public function businessBranch()
    {
        return $this->belongsTo(BusinessBranch::class);
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Get the parent (either Business or BusinessBranch)
     */
    public function parent()
    {
        return $this->business_id 
            ? $this->business 
            : $this->businessBranch;
    }

    /**
     * Check if official belongs to a branch
     */
    public function isBranchOfficial(): bool
    {
        return !is_null($this->business_branch_id);
    }

    /**
     * Check if official belongs to standalone business
     */
    public function isBusinessOfficial(): bool
    {
        return !is_null($this->business_id);
    }

    /**
     * Get social media link for a specific platform
     */
    public function getSocialLink(string $platform): ?string
    {
        if (!$this->social_accounts) {
            return null;
        }
        
        return $this->social_accounts[$platform] ?? null;
    }

    /**
     * Check if official has a social account on specific platform
     */
    public function hasSocial(string $platform): bool
    {
        return !empty($this->getSocialLink($platform));
    }

    /**
     * Get all active social accounts
     */
    public function getActiveSocialAccounts(): array
    {
        if (!$this->social_accounts) {
            return [];
        }

        return array_filter($this->social_accounts, fn($url) => !empty($url));
    }

    /**
     * Get count of social accounts
     */
    public function getSocialAccountsCount(): int
    {
        return count($this->getActiveSocialAccounts());
    }

    /**
     * Get parent business name (works for both business and branch)
     */
    public function getParentName(): string
    {
        if ($this->business_id) {
            return $this->business->business_name;
        }
        
        if ($this->business_branch_id) {
            return $this->businessBranch->branch_title;
        }

        return 'N/A';
    }

    // ============================================
    // Scopes
    // ============================================

    /**
     * Scope for active officials
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for officials of a specific business
     */
    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for officials of a specific branch
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('business_branch_id', $branchId);
    }

    /**
     * Scope ordered by display order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order', 'asc')->orderBy('name', 'asc');
    }

    // ============================================
    // Validation & Cleanup
    // ============================================

    /**
     * Boot method to ensure official belongs to either business OR branch
     */
    protected static function booted()
    {
        static::creating(function ($official) {
            // Ensure official belongs to either business OR branch, not both
            if ($official->business_id && $official->business_branch_id) {
                throw new \InvalidArgumentException('Official cannot belong to both business and branch. Choose one.');
            }

            // Ensure official belongs to at least one
            if (!$official->business_id && !$official->business_branch_id) {
                throw new \InvalidArgumentException('Official must belong to either a business or a branch.');
            }
        });

        static::updating(function ($official) {
            // Prevent changing parent after creation
            if ($official->isDirty(['business_id', 'business_branch_id'])) {
                $original = $official->getOriginal();
                if (($original['business_id'] && $official->business_branch_id) || 
                    ($original['business_branch_id'] && $official->business_id)) {
                    throw new \InvalidArgumentException('Cannot change official from business to branch or vice versa.');
                }
            }
        });

        static::deleting(function ($official) {
            // Clean up photo file when deleting official
            if ($official->photo) {
                Storage::disk('public')->delete($official->photo);
            }
        });
    }
}