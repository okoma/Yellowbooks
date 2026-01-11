<?php

// ============================================
// app/Models/UserPaymentMethod.php
// Saved payment methods (cards, bank accounts)
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'card_brand',
        'card_last4',
        'card_exp_month',
        'card_exp_year',
        'bank_name',
        'account_number',
        'account_name',
        'payment_gateway',
        'gateway_customer_code',
        'authorization_code',
        'is_default',
        'is_verified',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeCards($query)
    {
        return $query->where('type', 'card');
    }

    public function scopeBankAccounts($query)
    {
        return $query->where('type', 'bank_account');
    }

    // Helper methods
    public function makeDefault()
    {
        // Remove default from other methods
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function getDisplayName()
    {
        if ($this->type === 'card') {
            return $this->card_brand . ' •••• ' . $this->card_last4;
        }

        return $this->bank_name . ' - ' . $this->account_name;
    }

    public function isExpired()
    {
        if ($this->type !== 'card') {
            return false;
        }

        $expiry = now()->setYear($this->card_exp_year)->setMonth($this->card_exp_month)->endOfMonth();

        return $expiry->isPast();
    }
}