<?php

// ============================================
// app/Models/Referral.php
// Referral program
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_code',
        'referrer_reward',
        'referrer_credits',
        'referred_reward',
        'referred_credits',
        'status',
        'rewards_paid',
        'completed_at',
    ];

    protected $casts = [
        'referrer_reward' => 'decimal:2',
        'referred_reward' => 'decimal:2',
        'rewards_paid' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Helper methods
    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        if (!$this->rewards_paid) {
            $this->payRewards();
        }
    }

    public function payRewards()
    {
        // Pay referrer
        if ($this->referrer_reward > 0) {
            $this->referrer->wallet->deposit(
                $this->referrer_reward,
                'Referral reward for referring ' . $this->referred->name,
                $this
            );
        }

        if ($this->referrer_credits > 0) {
            $this->referrer->wallet->addCredits(
                $this->referrer_credits,
                'Referral credits for referring ' . $this->referred->name,
                $this
            );
        }

        // Pay referred
        if ($this->referred_reward > 0) {
            $this->referred->wallet->deposit(
                $this->referred_reward,
                'Welcome bonus from referral',
                $this
            );
        }

        if ($this->referred_credits > 0) {
            $this->referred->wallet->addCredits(
                $this->referred_credits,
                'Welcome bonus credits',
                $this
            );
        }

        $this->update(['rewards_paid' => true]);
    }
}
