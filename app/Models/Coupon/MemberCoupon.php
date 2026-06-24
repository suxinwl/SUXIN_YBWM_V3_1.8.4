<?php

namespace App\Models\Coupon;

use App\Models\Admin;
use App\Models\BaseModel;
use App\Models\Coupon\Coupon;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MemberCoupon extends BaseModel
{
    use HasFactory;
    protected $table = 'member_coupon';
    protected $fillable = ['storeId','uniacid', 'orderSn', 'userId', 'orderId', 'couponId', 'channel', 'sn', 'state', 'startTime', 'endTime'];
    protected $with = [
        'coupon', 'member'
    ];
    protected $appends = [
        'channelFormat', 'stateFormat'
    ];
    public function coupon()
    {
        return $this->hasOne(Coupon::class, 'id', 'couponId')->withTrashed();
    }
    public function windowscoupon()
    {
        return $this->hasOne(\App\Models\WindowCoupon\Coupon::class, 'id', 'couponId');
    }

    public function member()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'adminId');
    }

    public function regift()
    {
        return $this->hasMany(Regift::class, 'memberCouponId', 'id');
    }

    public function getStartTimeFormatAttribute()
    {
        return Carbon::createFromFormat("Y-m-d H:i:s", $this->startTime)->toDateString();
    }

    public function getEndTimeFormatAttribute()
    {
        return Carbon::createFromFormat("Y-m-d H:i:s", $this->endTime)->toDateString();
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->sn = empty($model->sn) ? CouponRandInt(10) : $model->sn;
        });
        static::created(function ($model) {
            $limitKey = "coupon:userlimit:{$model->couponId}{$model->userId}";
            $dayLimitKey = "coupon:userDaylimit:{$model->couponId}" . date("Ymd") . ":{$model->userId}";
            Cache::set($limitKey, (Cache::get($limitKey, 0) + 1));
            Cache::set($dayLimitKey, (Cache::get($dayLimitKey, 0) + 1));
        });
    }

    public function getChannelFormatAttribute()
    {
        $data = [
            1 => '领券中心',
            2 => "弹窗发放",
            3 => '系统赠送',
            4 => '新人礼包',
            5 => '消费有礼',
            6 => '集点有礼',
            7 => '优惠券包',
            8 => "日历签到",
            9 => "储值赠送",
            10 => "会员升级赠送",
            11 => "好友赠送",
            12 => "兑换活动",
            13 => "积分商城",
            14 => "优惠券包",
            15 => "完善资料",
            16 => '生日有礼',
            17 => "老带新-被邀奖励",
            18 => "老带新-邀请奖励",
            19 => "老带新-首次消费",
            20 => "口令红包赠送",
            21 => "权益卡赠送",
            22 => "权益卡周期赠送",
            23 => "回头唤醒"
        ];
        return $data[$this->channel];
    }

    public function getStateFormatAttribute()
    {
        $data = [
            0 => '已过期',
            1 => '待使用',
            2 => "已使用",
            3 => '已作废'
        ];
        return $data[$this->state];
    }
}
