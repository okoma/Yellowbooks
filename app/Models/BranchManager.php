<?php
// ============================================
// app/Models/BranchManager.php
// Managers assigned to specific branches with permissions
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchManager extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_branch_id',
        'user_id',
        'position',
        'employee_id',
        'phone',
        'email',
        'whatsapp',
        'permissions',
        'is_active',
        'is_primary',
        'assigned_by',
        'assigned_at',
        'removed_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'assigned_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ManagerActivityLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    // Helper methods - Permission Checks
    public function can($permission)
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->permissions) {
            return false;
        }

        return $this->permissions[$permission] ?? false;
    }

    public function canEditBranch()
    {
        return $this->can('can_edit_branch');
    }

    public function canManageProducts()
    {
        return $this->can('can_manage_products');
    }

    public function canRespondToReviews()
    {
        return $this->can('can_respond_to_reviews');
    }

    public function canViewLeads()
    {
        return $this->can('can_view_leads');
    }

    public function canRespondToLeads()
    {
        return $this->can('can_respond_to_leads');
    }

    public function canViewAnalytics()
    {
        return $this->can('can_view_analytics');
    }

    public function canAccessFinancials()
    {
        return $this->can('can_access_financials');
    }

    public function canManageStaff()
    {
        return $this->can('can_manage_staff');
    }

    // Update permissions
    public function updatePermissions(array $permissions)
    {
        $this->update(['permissions' => $permissions]);
    }

    public function grantPermission($permission)
    {
        $permissions = $this->permissions ?? [];
        $permissions[$permission] = true;
        $this->update(['permissions' => $permissions]);
    }

    public function revokePermission($permission)
    {
        $permissions = $this->permissions ?? [];
        $permissions[$permission] = false;
        $this->update(['permissions' => $permissions]);
    }

    // Status management
    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update([
            'is_active' => false,
            'removed_at' => now(),
        ]);
    }

    public function makePrimary()
    {
        // Remove primary from other managers of this branch
        static::where('business_branch_id', $this->business_branch_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    // Log activity
    public function logActivity($action, $description, $actionable = null, $oldValues = null, $newValues = null)
    {
        return ManagerActivityLog::create([
            'branch_manager_id' => $this->id,
            'business_branch_id' => $this->business_branch_id,
            'action' => $action,
            'description' => $description,
            'actionable_type' => $actionable ? get_class($actionable) : null,
            'actionable_id' => $actionable?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    // Events
    protected static function booted()
    {
        static::created(function ($manager) {
            // Update user's manager status
            $manager->user->updateManagerStatus();
        });

        static::deleted(function ($manager) {
            // Update user's manager status
            $manager->user->updateManagerStatus();
        });
    }
}
