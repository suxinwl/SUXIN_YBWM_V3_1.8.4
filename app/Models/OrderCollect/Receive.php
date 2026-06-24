<?php

namespace App\Models\OrderCollect;

use App\Models\Coupon\Coupon;
use App\Models\Member;
use App\Models\TakeoutOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receive extends Model
{
    use HasFactory;
    protected $table = 'order_collect_receive';
    public $_couponList;
    protected $fillable = ['couponCount', 'uniacid', 'collectId', 'orderId', 'integral', 'balance', 'userId', 'integral', 'balance', 'couponGive', 'issus', 'orderSn'];
    protected $casts =  [
        'couponGive' => 'array'
    ];
    protected $appends = [
        'couponList'
    ];
    public function order()
    {
        return $this->hasOne(TakeoutOrder::class, 'id', 'orderId');
    }
    public function member()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }
    public function ordercollect()
    {
        return $this->hasOne(OrderCollect::class, 'id', 'collectId');
    }
    public  function getCouponListAttribute()
    {
        if (!$this->_couponList) {
            $this->_couponList = Coupon::whereIN('id', $this->couponGive ?? [0])->get();
        }
        return $this->_couponList;
    }
}
