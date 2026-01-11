<?php

// ============================================
// app/Models/VerificationAttempt.php
// Audit trail for all verification attempts
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_verification_id',
        'verification_type',
        'status',
        'details',
        'metadata',
        'ip_address',
        'attempted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'attempted_at' => 'datetime',
    ];

    // Relationships
    public function verification()
    {
        return $this->belongsTo(BusinessVerification::class, 'business_verification_id');
    }

    // Helper methods
    public static function logAttempt($verificationId, $type, $status, $details = null, $metadata = [])
    {
        return static::create([
            'business_verification_id' => $verificationId,
            'verification_type' => $type,
            'status' => $status,
            'details' => $details,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'attempted_at' => now(),
        ]);
    }
}