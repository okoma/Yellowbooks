<?php

// ============================================
// app/Models/BusinessReport.php
// User reports for fake/spam businesses
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'reported_by',
        'reason',
        'description',
        'evidence',
        'status',
        'reviewed_by',
        'admin_notes',
        'reviewed_at',
        'action_taken',
    ];

    protected $casts = [
        'evidence' => 'array',
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReviewing($query)
    {
        return $query->where('status', 'reviewing');
    }

    // Helper methods
    public function markAsReviewing($adminId)
    {
        $this->update([
            'status' => 'reviewing',
            'reviewed_by' => $adminId,
        ]);
    }

    public function resolve($adminId, $action, $notes = null)
    {
        $this->update([
            'status' => 'resolved',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'action_taken' => $action,
            'admin_notes' => $notes,
        ]);
    }

    public function dismiss($adminId, $notes = null)
    {
        $this->update([
            'status' => 'dismissed',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }
}