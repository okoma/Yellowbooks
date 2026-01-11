<?php
// app/Models/SavedBusiness.php - COMPLETE FIXED VERSION

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedBusiness extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',           // For standalone businesses
        'business_branch_id',    // For multi-location businesses
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * User who saved the business/branch
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Standalone Business (if saved)
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Business Branch (if saved)
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
     * Check if saved item is a standalone business
     */
    public function isForBusiness(): bool
    {
        return !is_null($this->business_id);
    }

    /**
     * Check if saved item is a branch
     */
    public function isForBranch(): bool
    {
        return !is_null($this->business_branch_id);
    }

    /**
     * Toggle save status (save/unsave)
     * 
     * @param int $userId User ID
     * @param int|null $businessId Standalone business ID
     * @param int|null $branchId Branch ID
     * @return bool True if saved, false if unsaved
     */
    public static function toggle($userId, $businessId = null, $branchId = null): bool
    {
        if (!$businessId && !$branchId) {
            throw new \InvalidArgumentException('Must provide either businessId or branchId');
        }
        
        if ($businessId && $branchId) {
            throw new \InvalidArgumentException('Cannot provide both businessId and branchId');
        }

        $query = static::where('user_id', $userId);
        
        if ($businessId) {
            $query->where('business_id', $businessId);
        } else {
            $query->where('business_branch_id', $branchId);
        }
        
        $saved = $query->first();

        if ($saved) {
            $saved->delete();
            
            // Update aggregate stats
            if ($businessId) {
                Business::find($businessId)?->updateAggregateStats();
            } else {
                BusinessBranch::find($branchId)?->updateAggregateStats();
            }
            
            return false; // Unsaved
        }

        static::create([
            'user_id' => $userId,
            'business_id' => $businessId,
            'business_branch_id' => $branchId,
        ]);

        // Update aggregate stats
        if ($businessId) {
            Business::find($businessId)?->updateAggregateStats();
        } else {
            BusinessBranch::find($branchId)?->updateAggregateStats();
        }

        return true; // Saved
    }

    /**
     * Check if user has saved a business/branch
     * 
     * @param int $userId User ID
     * @param int|null $businessId Standalone business ID
     * @param int|null $branchId Branch ID
     * @return bool
     */
    public static function isSaved($userId, $businessId = null, $branchId = null): bool
    {
        if (!$businessId && !$branchId) {
            throw new \InvalidArgumentException('Must provide either businessId or branchId');
        }
        
        if ($businessId && $branchId) {
            throw new \InvalidArgumentException('Cannot provide both businessId and branchId');
        }

        $query = static::where('user_id', $userId);
        
        if ($businessId) {
            $query->where('business_id', $businessId);
        } else {
            $query->where('business_branch_id', $branchId);
        }
        
        return $query->exists();
    }

    /**
     * Get all saved standalone businesses for a user
     * 
     * @param int $userId User ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getSavedBusinesses($userId)
    {
        return static::where('user_id', $userId)
            ->whereNotNull('business_id')
            ->with('business')
            ->get();
    }

    /**
     * Get all saved branches for a user
     * 
     * @param int $userId User ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getSavedBranches($userId)
    {
        return static::where('user_id', $userId)
            ->whereNotNull('business_branch_id')
            ->with('branch')
            ->get();
    }

    /**
     * Get all saved items (both businesses and branches) for a user
     * 
     * @param int $userId User ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllSaved($userId)
    {
        return static::where('user_id', $userId)
            ->with(['business', 'branch'])
            ->get();
    }

    /**
     * Get count of saved businesses for a user
     */
    public static function getBusinessCount($userId): int
    {
        return static::where('user_id', $userId)
            ->whereNotNull('business_id')
            ->count();
    }

    /**
     * Get count of saved branches for a user
     */
    public static function getBranchCount($userId): int
    {
        return static::where('user_id', $userId)
            ->whereNotNull('business_branch_id')
            ->count();
    }

    /**
     * Get total count of saved items for a user
     */
    public static function getTotalCount($userId): int
    {
        return static::where('user_id', $userId)->count();
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope for saved standalone businesses only
     */
    public function scopeBusinesses($query)
    {
        return $query->whereNotNull('business_id')->whereNull('business_branch_id');
    }

    /**
     * Scope for saved branches only
     */
    public function scopeBranches($query)
    {
        return $query->whereNotNull('business_branch_id')->whereNull('business_id');
    }

    /**
     * Scope for a specific user's saved items
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for recent saves
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ============================================
    // VALIDATION
    // ============================================

    /**
     * Boot method to ensure saved item belongs to either business OR branch
     */
    protected static function booted()
    {
        static::creating(function ($saved) {
            // Ensure belongs to either business OR branch, not both
            if ($saved->business_id && $saved->business_branch_id) {
                throw new \Exception('SavedBusiness cannot belong to both business and branch. Choose one.');
            }

            // Ensure belongs to at least one
            if (!$saved->business_id && !$saved->business_branch_id) {
                throw new \Exception('SavedBusiness must belong to either a business or a branch.');
            }
        });
    }
}