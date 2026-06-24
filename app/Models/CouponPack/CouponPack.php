<?php

namespace App\Models\CouponPack;

use App\Models\BaseModel;
use App\Models\Collect;
use App\Models\Coupon\Coupon;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class CouponPack extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'coupon_pack';
    protected $fillable = ['storeId', 'uniacid', 'desc', 'name', 'sales', 'state', 'inventoryType', 'inventory', 'label', 'image', 'price', 'sellPrice', 'rule', 'startTime', 'endTime', 'couponGive', 'storeType', 'storeIds','weekArr'];
    protected $casts =  [
        'rule' => 'array',
        'storeIds' => 'array',
        'couponGive' => "array",
        'weekArr' => 'array'
    ];

    protected $appends = [
        'countdown'
    ];

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'coupon_pack_stores', 'couponPackId', 'storeId');
    }

    public function getCountdownAttribute()
    {
        $currentTime = time(); // 当前时间
        $targetTime = strtotime($this->endTime); // 目标日期的时间戳
        $countdown = date_diff(date_create(date('Y-m-d H:i:s', $currentTime)), date_create(date('Y-m-d H:i:s', $targetTime)));
        return json_decode($countdown->format('{"d":"%a","h":"%h","m":"%i"}'), true);
    }

    public  function getCouponListAttribute()
    {
        $ids = collect($this->couponGive)->pluck('id')->all();
        if ($ids) {
            return Coupon::whereIN('id', $ids)->get();
        }
        return [];
    }
    public function getRefundFormatAttribute()
    {
        if ($this->state == 7 ||  $this->state == 8) {
            $data = [
                7 => '待审批',
                8 => "已退款"
            ];
            return $data[$this->state];
        }
        if ($this->refundState == 2) {
            return '退款拒绝';
        }
        return '';
    }
}
