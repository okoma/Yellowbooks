<?php
// ============================================
// app/Models/Lead.php
// COMPLETE VERSION - Supports BOTH standalone businesses AND branches
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',           // NEW: For standalone businesses (direct relationship)
        'business_branch_id',    // For branches (nullable now)
        'user_id',              // User who submitted the lead (optional)
        'client_name',
        'email',
        'phone',
        'whatsapp',
        'lead_button_text',     // Type of inquiry (e.g., "Book Now", "Get Quote")
        'custom_fields',        // JSON field for additional form data
        'status',               // 'new', 'contacted', 'qualified', 'converted', 'lost'
        'is_replied',
        'replied_at',
        'reply_message',
        'notes',                // Internal notes (not visible to customer)
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'is_replied' => 'boolean',
        'replied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'new',
        'is_replied' => false,
    ];

    // ===== RELATIONSHIPS =====
    
    /**
     * Business (for standalone businesses WITHOUT branches)
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Branch (for multi-location businesses WITH branches)
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    /**
     * User who submitted the lead (optional - can be guest)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ===== HELPER METHODS =====

    /**
     * Get the parent business (either direct or through branch)
     * This always returns a Business model regardless of lead type
     */
    public function getParentBusiness()
    {
        // If direct business relationship exists
        if ($this->business_id) {
            return $this->business;
        }
        
        // If branch relationship exists, get business through branch
        if ($this->business_branch_id && $this->branch) {
            return $this->branch->business;
        }
        
        return null;
    }

    /**
     * Get the location name (business or branch)
     */
    public function getLocationName(): ?string
    {
        if ($this->branch) {
            return $this->branch->branch_name;
        }
        
        if ($this->business) {
            return $this->business->business_name;
        }
        
        return null;
    }

    /**
     * Check if lead belongs to a standalone business
     */
    public function isStandalone(): bool
    {
        return $this->business_id && !$this->business_branch_id;
    }

    /**
     * Check if lead belongs to a branch
     */
    public function isBranchLead(): bool
    {
        return $this->business_branch_id !== null;
    }

    /**
     * Check if lead is from a registered user
     */
    public function isFromRegisteredUser(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Check if lead is from a guest
     */
    public function isFromGuest(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Mark lead as contacted
     */
    public function markAsContacted(): void
    {
        $this->update(['status' => 'contacted']);
    }

    /**
     * Mark lead as qualified
     */
    public function markAsQualified(): void
    {
        $this->update(['status' => 'qualified']);
    }

    /**
     * Mark lead as converted
     */
    public function markAsConverted(): void
    {
        $this->update(['status' => 'converted']);
    }

    /**
     * Mark lead as lost
     */
    public function markAsLost(): void
    {
        $this->update(['status' => 'lost']);
    }

    /**
     * Mark lead as replied
     */
    public function markAsReplied(string $message): void
    {
        $this->update([
            'is_replied' => true,
            'replied_at' => now(),
            'reply_message' => $message,
        ]);
    }

    /**
     * Get contact preference (WhatsApp, Phone, or Email)
     */
    public function getPreferredContact(): string
    {
        if ($this->whatsapp) {
            return 'WhatsApp: ' . $this->whatsapp;
        }
        
        if ($this->phone) {
            return 'Phone: ' . $this->phone;
        }
        
        if ($this->email) {
            return 'Email: ' . $this->email;
        }
        
        return 'No contact information';
    }

    /**
     * Get WhatsApp link
     */
    public function getWhatsAppLink(): ?string
    {
        if (!$this->whatsapp) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $this->whatsapp);
        
        // Add country code if not present
        if (!str_starts_with($phone, '234')) {
            // Remove leading zero if present
            $phone = ltrim($phone, '0');
            $phone = '234' . $phone;
        }

        return 'https://wa.me/' . $phone;
    }

    /**
     * Get email link
     */
    public function getEmailLink(): ?string
    {
        if (!$this->email) {
            return null;
        }

        return 'mailto:' . $this->email;
    }

    /**
     * Get phone link
     */
    public function getPhoneLink(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        return 'tel:' . $this->phone;
    }

    /**
     * Get time since lead was created
     */
    public function getTimeSinceCreated(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if lead is recent (within last 24 hours)
     */
    public function isRecent(): bool
    {
        return $this->created_at->isToday() || $this->created_at->isYesterday();
    }

    /**
     * Check if lead is old (more than 30 days)
     */
    public function isOld(): bool
    {
        return $this->created_at->diffInDays(now()) > 30;
    }

    // ===== STATUS CHECKS =====

    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function isContacted(): bool
    {
        return $this->status === 'contacted';
    }

    public function isQualified(): bool
    {
        return $this->status === 'qualified';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    public function isReplied(): bool
    {
        return $this->is_replied === true;
    }

    // ===== SCOPES =====

    /**
     * Scope for new leads
     */
    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope for contacted leads
     */
    public function scopeContacted(Builder $query): Builder
    {
        return $query->where('status', 'contacted');
    }

    /**
     * Scope for qualified leads
     */
    public function scopeQualified(Builder $query): Builder
    {
        return $query->where('status', 'qualified');
    }

    /**
     * Scope for converted leads
     */
    public function scopeConverted(Builder $query): Builder
    {
        return $query->where('status', 'converted');
    }

    /**
     * Scope for lost leads
     */
    public function scopeLost(Builder $query): Builder
    {
        return $query->where('status', 'lost');
    }

    /**
     * Scope for unreplied leads
     */
    public function scopeUnreplied(Builder $query): Builder
    {
        return $query->where('is_replied', false);
    }

    /**
     * Scope for replied leads
     */
    public function scopeReplied(Builder $query): Builder
    {
        return $query->where('is_replied', true);
    }

    /**
     * Scope for leads by business
     */
    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for leads by branch
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('business_branch_id', $branchId);
    }

    /**
     * Scope for standalone business leads only
     */
    public function scopeStandalone(Builder $query): Builder
    {
        return $query->whereNotNull('business_id')
                     ->whereNull('business_branch_id');
    }

    /**
     * Scope for branch leads only
     */
    public function scopeBranchOnly(Builder $query): Builder
    {
        return $query->whereNotNull('business_branch_id');
    }

    /**
     * Scope for recent leads (last 7 days)
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(7));
    }

    /**
     * Scope for today's leads
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for this week's leads
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope for this month's leads
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    /**
     * Scope for leads from registered users
     */
    public function scopeFromRegisteredUsers(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope for leads from guests
     */
    public function scopeFromGuests(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    // ===== ACCESSORS =====

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'new' => 'warning',
            'contacted' => 'info',
            'qualified' => 'primary',
            'converted' => 'success',
            'lost' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    // ===== BOOT METHOD =====

    protected static function booted(): void
    {
        // Ensure at least one parent is set
        static::creating(function (Lead $lead) {
            if (!$lead->business_id && !$lead->business_branch_id) {
                throw new \Exception('Lead must belong to either a Business or a BusinessBranch');
            }
        });

        // Auto-set replied_at when is_replied changes to true
        static::updating(function (Lead $lead) {
            if ($lead->isDirty('is_replied') && $lead->is_replied && !$lead->replied_at) {
                $lead->replied_at = now();
            }
        });
    }
}