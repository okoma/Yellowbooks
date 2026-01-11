<?php
// ============================================
// app/Models/CouponUsage.php
// Track who used which coupons
// ============================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    use HasFactory;
    
    protected $table = 'coupon_usage';
    
    protected $fillable = [
        'coupon_id',
        'user_id',
        'transaction_id',
        'discount_amount',
    ];
    
    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];
    
    // Relationships
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}