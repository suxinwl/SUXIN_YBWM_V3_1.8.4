<?php
namespace App\Models\WindowCoupon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;
use App\Models\WindowCoupon\Coupon;
class CouponReceive extends Model
{
    use HasFactory;
    protected $table = 'window_coupon_receive';
    protected $fillable = ['uniacid', 'windowCouponId', 'userId','balance','integral','coupon','data'];
    protected $casts =  [
        'coupon'=> 'array',
        'data' => 'array',
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
    public function  member()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }
    public function  window()
    {
        return $this->hasOne(Coupon::class, 'id', 'windowCouponId');
    }
}
