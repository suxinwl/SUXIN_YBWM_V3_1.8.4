<?php

namespace App\Models\GiftBig;

use App\Models\BaseModel;
use App\Models\Coupon\Coupon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftBig extends BaseModel
{
    public $_couponList;
    protected $table = 'member_gift_big';
    use HasFactory, SoftDeletes;
    protected $fillable = ['uniacid','images','storeId','name', 'startTime', 'endTime', 'balanceSwitch', 'balance', 'integralSwitch', 'integral', 'couponSwitch', 'couponGive'];
    protected $casts =  [
        'couponGive' => 'array',
    ];
    protected $appends = [
        'stateFormat'
    ];

    public function  receives()
    {
        return $this->hasMany(Receive::class, 'bigId', 'id');
    }

    public  function getCouponListAttribute()
    {
        if (!$this->_couponList) {
            $ids = collect($this->couponGive)->pluck('id')->all();
            if ($ids) {
                $this->_couponList = Coupon::whereIN('id', $ids)->get();
            }
        }
        return $this->_couponList;
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
