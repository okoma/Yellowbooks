<?php

// ============================================
// app/Models/BusinessClaim.php
// Claim request workflow
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'claim_message',
        'claimant_position',
        'verification_phone',
        'verification_email',
        'phone_verified',
        'email_verified',
        'status',
        'reviewed_by',
        'rejection_reason',
        'admin_notes',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'phone_verified' => 'boolean',
        'email_verified' => 'boolean',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Helper methods
    public function approve($adminId)
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'approved_at' => now(),
        ]);

        // Update business
        $this->business->update([
            'is_claimed' => true,
            'claimed_by' => $this->user_id,
            'claimed_at' => now(),
        ]);
    }

    public function reject($adminId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }
}