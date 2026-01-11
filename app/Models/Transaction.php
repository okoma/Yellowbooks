<?php

// ============================================
// app/Models/Transaction.php
// All payment transactions
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'transaction_ref',
        'payment_gateway_ref',
        'transactionable_type',
        'transactionable_id',
        'amount',
        'currency',
        'exchange_rate',
        'payment_method',
        'status',
        'description',
        'metadata',
        'gateway_response',
        'authorization_code',
        'is_refunded',
        'refund_amount',
        'refund_reason',
        'refunded_at',
        'paid_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'metadata' => 'array',
        'gateway_response' => 'array',
        'is_refunded' => 'boolean',
        'refund_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionable()
    {
        return $this->morphTo();
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Helper methods
    public static function generateRef()
    {
        return 'TXN-' . strtoupper(uniqid()) . '-' . time();
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed()
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);
    }

    public function refund($amount = null, $reason = null)
    {
        $refundAmount = $amount ?? $this->amount;

        $this->update([
            'status' => 'refunded',
            'is_refunded' => true,
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ]);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }
}