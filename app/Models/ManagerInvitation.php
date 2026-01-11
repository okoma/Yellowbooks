<?php

// ============================================
// app/Models/ManagerInvitation.php
// Invite system for branch managers
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ManagerInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_branch_id',
        'invited_by',
        'email',
        'invitation_token',
        'position',
        'permissions',
        'status',
        'expires_at',
        'accepted_at',
        'user_id',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<=', now());
    }

    // Helper methods
    public static function createInvitation($branchId, $email, $invitedBy, $position = 'Branch Manager', $permissions = [])
    {
        return static::create([
            'business_branch_id' => $branchId,
            'invited_by' => $invitedBy,
            'email' => $email,
            'invitation_token' => Str::random(64),
            'position' => $position,
            'permissions' => $permissions,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function isValid()
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    public function accept($userId)
    {
        if (!$this->isValid()) {
            throw new \Exception('Invitation is not valid');
        }

        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'user_id' => $userId,
        ]);

        // Create branch manager record
        $manager = BranchManager::create([
            'business_branch_id' => $this->business_branch_id,
            'user_id' => $userId,
            'position' => $this->position,
            'permissions' => $this->permissions,
            'assigned_by' => $this->invited_by,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        return $manager;
    }

    public function decline()
    {
        $this->update(['status' => 'declined']);
    }

    public function resend()
    {
        $this->update([
            'invitation_token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        // TODO: Send email notification
    }

    public function getInvitationUrl()
    {
        return route('manager.invitation.accept', ['token' => $this->invitation_token]);
    }

    // Mark expired invitations
    public static function markExpired()
    {
        return static::where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }
}
