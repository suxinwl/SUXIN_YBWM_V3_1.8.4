<?php

namespace App\Models\OrderCollect;

use App\Models\BaseModel;
use App\Models\Coupon\Coupon;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCollect extends BaseModel
{
    public $_couponList;
    use HasFactory;
    protected $table = 'order_collect';
    protected $fillable = [
        'storeId', 'images', 'body', 'type', 'num', 'numName', 'uniacid', 'name', 'scenario', 'startTime', 'endTime', 'balanceSwitch', 'balance', 'integralSwitch', 'integral', 'couponSwitch', 'couponGive', 'state', 'storeType', 'storeIds'
    ];
    protected $appends = [
        'couponList', 'stateFormat'
    ];
    protected $casts =  [
        'scenario' => 'array',
        'storeIds' => 'array',
        'couponGive' => 'array'
    ];

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'order_collect_stores', 'collectId', 'storeId');
    }

    public function getUserDataAttribute()
    {
        $userId = auth('user')->user()->id;
        return User::where('uniacid', $this->uniacid)->where('userId', $userId)->where('collectId', $this->id)->first();
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
}
