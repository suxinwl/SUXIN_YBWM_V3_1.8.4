<?php

namespace App\Models\WindowCoupon;

use App\Models\Coupon\Coupon as CouponCoupon;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    public $_couponList;
    use HasFactory;
    protected $table = 'window_coupon';
    protected $fillable = ['name', 'images', 'uniacid', 'storeId', 'startTime', 'endTime', 'pos', 'receiveType', 'couponGive', 'storeType', 'storeIds'];
    protected $appends = [
        'userReceive', 'stateFormat'
    ];
    protected $casts =  [
        'storeIds' => 'array',
        'couponGive' => 'array'
    ];

    public function  receives()
    {
        return $this->hasMany(CouponReceive::class, 'windowCouponId', 'id');
    }

    public function  getUserReceiveAttribute()
    {
        $user = auth('user')->user();
        if ($user) {
            return $this->receives()->where('userId', $user->id)->first();
        }
        return null;
    }


    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->sn = CouponRandInt(10);
        });
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'window_coupon_stores', 'windowCouponId', 'storeId');
    }
    public function getStateFormatAttribute()
    {
        if (time() < strtotime($this->startTime)) {
            return "未开始";
        } elseif (time() >= strtotime($this->startTime) && time() <= strtotime($this->endTime)) {
            return "进行中";
        } else {
            return "已结束";
        }
    }

    public  function getCouponListAttribute()
    {
        if (!$this->_couponList) {
            $ids = collect($this->couponGive)->pluck('id')->all();
            if ($ids) {
                $this->_couponList = CouponCoupon::whereIN('id', $ids)->get();
            }
        }
        return $this->_couponList;
    }
}
