<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use App\Models\Member;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FullSub\FullSub;

class Discount extends BaseModel
{
    use HasFactory;
    protected $table = 'order_discount';
    protected $fillable = ['notes', 'prentOrderSn','reason', 'uniacid', 'orderId', 'money', 'type', 'title', 'activityName', 'activityId', 'storeId', 'userId', 'orderSn'];

    public function  fullsub()
    {
        return $this->hasOne(Fullsub::class, 'id', 'activityId');
    }
    public function  member()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }
    public function  store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
    public function  order()
    {
        return $this->hasOne(\App\Models\TakeoutOrder::class, 'id', 'orderId');
    }
}
