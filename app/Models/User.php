<?php
// ============================================
// app/Models/User.php - COMPLETE UPDATED VERSION
// Includes manager relationships and complete role system
// ============================================

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'avatar',
        'bio',
        'is_active',
        'is_banned',
        'ban_reason',
        'banned_at',
        'last_login_at',
        'last_login_ip',
        'referral_code',
        'referred_by',
        'is_branch_manager',
        'managing_branches_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'role' => UserRole::class,
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_banned' => 'boolean',
        'banned_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_branch_manager' => 'boolean',
    ];

    // ============================================
    // FILAMENT MULTI-PANEL ACCESS CONTROL
    // ============================================

    /**
     * Determine which Filament panel(s) the user can access
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Check if user is banned or inactive
        if ($this->is_banned || !$this->is_active) {
            return false;
        }

        // Check panel access based on role
        return match($panel->getId()) {
            'admin' => $this->isAdmin() || $this->isModerator(),
            'business' => $this->isBusinessOwner() || $this->isBranchManagerRole(),
            'customer' => $this->isCustomer(),
            default => false,
        };
    }

    /**
     * Get user's display name for Filament
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * Get user's avatar for Filament
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    /**
     * Get the default panel for user's role
     */
    public function getDefaultPanel(): string
    {
        return match(true) {
            $this->isAdmin() || $this->isModerator() => 'admin',
            $this->isBusinessOwner() || $this->isBranchManagerRole() => 'business',
            $this->isCustomer() => 'customer',
            default => 'customer',
        };
    }

    /**
     * Get panel URL for this user
     */
    public function getPanelUrl(): string
    {
        return match($this->getDefaultPanel()) {
            'admin' => '/admin',
            'business' => '/dashboard',
            'customer' => '/customer',
            default => '/customer',
        };
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function claimedBusinesses()
    {
        return $this->hasMany(Business::class, 'claimed_by');
    }

    public function businessClaims()
    {
        return $this->hasMany(BusinessClaim::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function savedBusinesses()
    {
        return $this->belongsToMany(BusinessBranch::class, 'saved_businesses', 'user_id', 'business_branch_id')
            ->withTimestamps();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function branchManagerAssignments()
    {
        return $this->hasMany(BranchManager::class);
    }

    public function activeBranchManagers()
    {
        return $this->hasMany(BranchManager::class)->where('is_active', true);
    }

    public function managedBranches()
    {
        return $this->belongsToMany(
            BusinessBranch::class,
            'branch_managers',
            'user_id',
            'business_branch_id'
        )
        ->withPivot(['position', 'permissions', 'is_active', 'is_primary'])
        ->wherePivot('is_active', true);
    }

    public function managerInvitations()
    {
        return $this->hasMany(ManagerInvitation::class, 'user_id');
    }

    public function pendingManagerInvitations()
    {
        return $this->hasMany(ManagerInvitation::class, 'user_id')
            ->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function sentManagerInvitations()
    {
        return $this->hasMany(ManagerInvitation::class, 'invited_by');
    }
  
    /**
 * User Preferences
 */
public function preferences()
{
    return $this->hasOne(UserPreference::class);
}

/**
 * Get preferences or create default ones
 */
public function getPreferencesAttribute()
{
    return UserPreference::getForUser($this->id);
}

    // ============================================
    // ROLE CHECK METHODS
    // ============================================

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isModerator(): bool
    {
        return $this->role === UserRole::MODERATOR;
    }

    public function isBusinessOwner(): bool
    {
        return $this->role === UserRole::BUSINESS_OWNER;
    }

    public function isBranchManagerRole(): bool
    {
        return $this->role === UserRole::BRANCH_MANAGER;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::MODERATOR]);
    }

    public function canManageBusinesses(): bool
    {
        return $this->role === UserRole::BUSINESS_OWNER || $this->is_branch_manager;
    }

    // ============================================
    // Manager Helper Methods
    // ============================================

    public function isBranchManager()
    {
        return $this->is_branch_manager && $this->managing_branches_count > 0;
    }

    public function updateManagerStatus()
    {
        $count = $this->activeBranchManagers()->count();
        
        $this->update([
            'is_branch_manager' => $count > 0,
            'managing_branches_count' => $count,
        ]);
    }

    public function canManageBranch($branchId)
    {
        return $this->activeBranchManagers()
            ->where('business_branch_id', $branchId)
            ->exists();
    }

    public function getManagerPermissionsForBranch($branchId)
    {
        $manager = $this->activeBranchManagers()
            ->where('business_branch_id', $branchId)
            ->first();

        return $manager ? $manager->permissions : [];
    }

    public function canPerformAction($branchId, $permission)
    {
        $manager = $this->activeBranchManagers()
            ->where('business_branch_id', $branchId)
            ->first();

        return $manager && $manager->can($permission);
    }

    // ============================================
    // PERMISSION SYSTEM
    // ============================================

    public function can($ability, $arguments = []): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return match($ability) {
            'create-business' => $this->isBusinessOwner(),
            'edit-business' => $this->canEditBusiness($arguments),
            'delete-business' => $this->canDeleteBusiness($arguments),
            'create-branch' => $this->ownsBusinessForBranch($arguments),
            'edit-branch' => $this->canEditBranch($arguments),
            'delete-branch' => $this->ownsBranch($arguments),
            'manage-products' => $this->canManageProducts($arguments),
            'respond-to-review' => $this->canRespondToReview($arguments),
            'delete-review' => $this->isAdmin(),
            'view-leads' => $this->canViewLeads($arguments),
            'respond-to-leads' => $this->canRespondToLeads($arguments),
            'view-analytics' => $this->canViewAnalytics($arguments),
            'moderate-content' => $this->isStaff(),
            'approve-claims' => $this->isAdmin(),
            'approve-verifications' => $this->isAdmin(),
            'assign-managers' => $this->ownsBusinessForBranch($arguments),
            default => false,
        };
    }

    private function canEditBusiness($business): bool
    {
        if ($this->isAdmin()) return true;
        
        if ($this->isBusinessOwner() && $business->user_id === $this->id) {
            return true;
        }
        
        return false;
    }

    private function canEditBranch($branch): bool
    {
        if ($this->isAdmin()) return true;
        
        if ($branch->business->user_id === $this->id) {
            return true;
        }
        
        if ($this->is_branch_manager) {
            return $this->canPerformAction($branch->id, 'can_edit_branch');
        }
        
        return false;
    }

    private function canManageProducts($branch): bool
    {
        if ($this->isAdmin()) return true;
        
        if ($branch->business->user_id === $this->id) {
            return true;
        }
        
        if ($this->is_branch_manager) {
            return $this->canPerformAction($branch->id, 'can_manage_products');
        }
        
        return false;
    }

    private function canViewLeads($branch): bool
    {
        if ($this->isAdmin()) return true;
        
        if ($branch->business->user_id === $this->id) {
            return true;
        }
        
        if ($this->is_branch_manager) {
            return $this->canPerformAction($branch->id, 'can_view_leads');
        }
        
        return false;
    }

    private function canRespondToLeads($branch): bool
    {
        if ($this->isAdmin()) return true;
        
        if ($branch->business->user_id === $this->id) {
            return true;
        }
        
        if ($this->is_branch_manager) {
            return $this->canPerformAction($branch->id, 'can_respond_to_leads');
        }
        
        return false;
    }

    private function canRespondToReview($review): bool
    {
        if ($this->isAdmin()) return true;
        
        $branch = $review->branch;
        
        if ($branch->business->user_id === $this->id) {
            return true;
        }
        
        if ($this->is_branch_manager) {
            return $this->canPerformAction($branch->id, 'can_respond_to_reviews');
        }
        
        return false;
    }

    private function canViewAnalytics($branch): bool
    {
        if ($this->isAdmin()) return true;
        
        if ($branch->business->user_id === $this->id) {
            return true;
        }
        
        if ($this->is_branch_manager) {
            return $this->canPerformAction($branch->id, 'can_view_analytics');
        }
        
        return false;
    }

    private function ownsBranch($branch): bool
    {
        return $branch->business->user_id === $this->id;
    }

    private function ownsBusinessForBranch($branch): bool
    {
        if (is_object($branch) && isset($branch->business)) {
            return $branch->business->user_id === $this->id;
        }
        return false;
    }

    private function canDeleteBusiness($business): bool
    {
        return $this->isAdmin() || 
               ($this->isBusinessOwner() && $business->user_id === $this->id);
    }

    // ============================================
    // ROLE ASSIGNMENT METHODS
    // ============================================

    public function assignRole(string|UserRole $role): void
    {
        $this->update(['role' => $role]);
    }

    public function promoteToBusinessOwner(): void
    {
        if ($this->isCustomer()) {
            $this->assignRole(UserRole::BUSINESS_OWNER);
        }
    }

    public function promoteToModerator(): void
    {
        if (!$this->isAdmin()) {
            $this->assignRole(UserRole::MODERATOR);
        }
    }

    public function promoteToAdmin(): void
    {
        $this->assignRole(UserRole::ADMIN);
    }

    public function demoteToCustomer(): void
    {
        if (!$this->isAdmin()) {
            $this->assignRole(UserRole::CUSTOMER);
        }
    }

    // ============================================
    // Other Helper Methods
    // ============================================

    public function hasActiveSubscription()
    {
        return $this->subscription()->exists();
    }
}