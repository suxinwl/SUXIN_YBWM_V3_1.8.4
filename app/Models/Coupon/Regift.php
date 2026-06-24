<?php

namespace App\Models\Coupon;

use App\Models\BaseModel;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regift extends BaseModel
{
    use HasFactory;
    protected $table = 'coupon_regift';
    protected $fillable = ['uniacid', 'userId', 'couponId', 'memberCouponId', 'memberCouponId', 'state', 'expiredTime'];
    protected $with = [
        'receiveMember', 'member', 'coupon'
    ];

    protected $appends = [
        'stateFormat'
    ];

    public function coupon()
    {
        return $this->hasOne(Coupon::class, 'id', 'couponId');
    }

    public function memberCoupon()
    {
        return $this->hasOne(MemberCoupon::class, 'id', 'memberCouponId');
    }
    public function member()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }

    public function receiveMember()
    {
        return $this->hasOne(Member::class, 'id', 'receiveMemberId');
    }

    public function getStateFormatAttribute()
    {
        $data = [
            0 => '待领取',
            1 => '已领取',
            2 => '已取消'
        ];
        return $data[$this->state];
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if (!$model->exists) {
                $model->memberCoupon->state = 4;
                $model->memberCoupon->save();
            }
            if ($model->getOriginal('state') == 0 && $model->state == 2) {
                $model->expiredTime = null;
                $model->memberCoupon->state = 1;
                $model->memberCoupon->save();
            }
        });
    }
}
