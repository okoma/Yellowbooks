<?php

// ============================================
// app/Models/BusinessVerification.php
// Multi-step verification: CAC, Location, Email, Website
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'business_claim_id',
        'submitted_by',
        'cac_number',
        'cac_document',
        'cac_verified',
        'cac_notes',
        'office_address',
        'office_photo',
        'office_latitude',
        'office_longitude',
        'location_verified',
        'location_notes',
        'business_email',
        'email_verification_token',
        'email_verified',
        'email_verified_at',
        'email_notes',
        'website_url',
        'meta_tag_code',
        'website_verified',
        'website_verified_at',
        'website_notes',
        'additional_documents',
        'status',
        'verification_score',
        'verified_by',
        'rejection_reason',
        'admin_feedback',
        'verified_at',
        'resubmission_count',
        'last_resubmitted_at',
    ];

    protected $casts = [
        'cac_verified' => 'boolean',
        'office_latitude' => 'decimal:7',
        'office_longitude' => 'decimal:7',
        'location_verified' => 'boolean',
        'email_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'website_verified' => 'boolean',
        'website_verified_at' => 'datetime',
        'additional_documents' => 'array',
        'verified_at' => 'datetime',
        'last_resubmitted_at' => 'datetime',
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function claim()
    {
        return $this->belongsTo(BusinessClaim::class, 'business_claim_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function attempts()
    {
        return $this->hasMany(VerificationAttempt::class);
    }

    // Helper methods
    public function calculateScore()
    {
        $score = 0;

        if ($this->cac_verified) $score += 40;
        if ($this->location_verified) $score += 30;
        if ($this->email_verified) $score += 20;
        if ($this->website_verified) $score += 10;

        $this->update(['verification_score' => $score]);

        return $score;
    }

    public function getVerificationLevel()
    {
        $score = $this->verification_score;

        if ($score >= 90) return 'premium';
        if ($score >= 70) return 'standard';
        if ($score >= 40) return 'basic';
        
        return 'none';
    }

    public function approve($adminId)
    {
        $this->calculateScore();
        $level = $this->getVerificationLevel();

        $this->update([
            'status' => 'approved',
            'verified_by' => $adminId,
            'verified_at' => now(),
        ]);

        // Update business
        $this->business->update([
            'is_verified' => true,
            'verification_level' => $level,
            'verification_score' => $this->verification_score,
            'current_verification_id' => $this->id,
        ]);
    }

    public function reject($adminId, $reason, $feedback = null)
    {
        $this->update([
            'status' => 'rejected',
            'verified_by' => $adminId,
            'rejection_reason' => $reason,
            'admin_feedback' => $feedback,
        ]);
    }

    public function requestResubmission($adminId, $feedback)
    {
        $this->update([
            'status' => 'requires_resubmission',
            'verified_by' => $adminId,
            'admin_feedback' => $feedback,
        ]);
    }

    public function resubmit()
    {
        $this->update([
            'status' => 'pending',
            'resubmission_count' => $this->resubmission_count + 1,
            'last_resubmitted_at' => now(),
        ]);
    }
}