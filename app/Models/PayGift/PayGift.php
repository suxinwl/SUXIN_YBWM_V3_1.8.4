<?php

namespace App\Models\PayGift;

use App\Models\Coupon\Coupon;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayGift extends Model
{
    public $_couponList;
    use HasFactory;
    protected $table = 'pay_gift';
    protected $fillable = [
        'uniacid', 'name','storeId', 'logo', 'money', 'scenario', 'startTime', 'endTime', 'balanceSwitch', 'balance', 'integralSwitch', 'integral', 'couponSwitch', 'couponGive', 'state', 'userType', 'userIds', 'storeType', 'storeIds'
    ];
    protected $appends = [
        'couponList', 'stateFormat'
    ];
    protected $casts =  [
        'scenario' => 'array',
        'goodsIds' => 'array',
        'storeIds' => 'array',
        'couponGive' => 'array',
        'userIds' => 'array'
    ];

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'pay_gift_stores', 'payGiftId', 'storeId');
    }

    public  function getCouponListAttribute()
    {
        if (!$this->_couponList) {
            $ids = collect($this->couponGive)->pluck('id')->all();
            if ($ids) {
                $this->_couponList = Coupon::whereIN('id', $ids)->get();
            }
        }
        return $this->_couponList?:[];
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

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->sn = CouponRandInt(10);
        });
    }
}
