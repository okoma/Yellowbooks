<?php
// ============================================
// app/Models/Wallet.php
// User wallet for ad credits and cash
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'ad_credits',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // Helper methods
    public function deposit($amount, $description = null, $reference = null)
    {
        $balanceBefore = $this->balance;
        $this->increment('balance', $amount);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Wallet deposit',
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    public function withdraw($amount, $description = null, $reference = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $balanceBefore = $this->balance;
        $this->decrement('balance', $amount);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'withdrawal',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Wallet withdrawal',
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    public function purchase($amount, $description, $reference = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $balanceBefore = $this->balance;
        $this->decrement('balance', $amount);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'purchase',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    public function addCredits($credits, $description = null, $reference = null)
    {
        $creditsBefore = $this->ad_credits;
        $this->increment('ad_credits', $credits);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'credit_purchase',
            'credits' => $credits,
            'credits_before' => $creditsBefore,
            'credits_after' => $this->ad_credits,
            'description' => $description ?? 'Ad credits added',
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    public function useCredits($credits, $description, $reference = null)
    {
        if ($this->ad_credits < $credits) {
            throw new \Exception('Insufficient ad credits');
        }

        $creditsBefore = $this->ad_credits;
        $this->decrement('ad_credits', $credits);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'credit_usage',
            'credits' => $credits,
            'credits_before' => $creditsBefore,
            'credits_after' => $this->ad_credits,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    public function hasBalance($amount)
    {
        return $this->balance >= $amount;
    }

    public function hasCredits($credits)
    {
        return $this->ad_credits >= $credits;
    }
}