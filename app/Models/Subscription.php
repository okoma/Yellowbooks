<?php

// ============================================
// app/Models/Subscription.php
// User's active subscription
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'business_id',
        'subscription_code',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'paused_at',
        'auto_renew',
        'cancellation_reason',
        'branches_used',
        'products_used',
        'team_members_used',
        'photos_used',
        'ad_credits_used',
        'payment_method',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'paused_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('ends_at', '>', now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<=', now());
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function isTrialing()
    {
        return $this->status === 'trialing' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isExpired()
    {
        return $this->ends_at->isPast();
    }

    public function daysRemaining()
    {
        return max(0, now()->diffInDays($this->ends_at, false));
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);
    }

    public function pause()
    {
        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);
    }

    public function resume()
    {
        $this->update([
            'status' => 'active',
            'paused_at' => null,
        ]);
    }

    public function renew($duration = 30)
    {
        $this->update([
            'ends_at' => $this->ends_at->addDays($duration),
        ]);
    }

    public function canAddBranch()
    {
        return $this->plan->max_branches === null || $this->branches_used < $this->plan->max_branches;
    }

    public function canAddProduct()
    {
        return $this->plan->max_products === null || $this->products_used < $this->plan->max_products;
    }
}