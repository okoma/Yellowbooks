<?php

// ============================================
// app/Models/ManagerActivityLog.php
// Track all manager actions for audit trail
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagerActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_manager_id',
        'business_branch_id',
        'action',
        'description',
        'actionable_type',
        'actionable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relationships
    public function manager()
    {
        return $this->belongsTo(BranchManager::class, 'branch_manager_id');
    }

    public function branch()
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function actionable()
    {
        return $this->morphTo();
    }

    // Helper methods
    public static function log($managerId, $branchId, $action, $description, $actionable = null, $oldValues = null, $newValues = null)
    {
        return static::create([
            'branch_manager_id' => $managerId,
            'business_branch_id' => $branchId,
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

    public function getChangedFields()
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changed = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changed[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changed;
    }

    public function getFormattedChanges()
    {
        $changes = $this->getChangedFields();
        $formatted = [];

        foreach ($changes as $field => $values) {
            $formatted[] = ucfirst(str_replace('_', ' ', $field)) . 
                           " changed from '{$values['old']}' to '{$values['new']}'";
        }

        return implode(', ', $formatted);
    }

    // Scopes
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
}