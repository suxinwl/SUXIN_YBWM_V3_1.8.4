<?php

namespace App\Models\WordCoupon;

use App\Models\Coupon\Coupon as CouponCoupon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Coupon extends Model
{
    public $_couponList;
    use HasFactory;
    protected $table = 'word_coupon';
    protected $fillable = [
        'name',
        'uniacid',
        'logo',
        'word',
        'state',
        'sort',
        'inventoryLimit',
        'body',
        'couponGive',
        'couponSwitch',
        'balanceSwitch',
        'balance',
        'integralSwitch',
        'integral',
        'startTime',
        'endTime',
        'bg',
        'storeId'
    ];

    protected $casts =  [
        'inventoryLimit' => 'array',
        'couponGive' => 'array'
    ];

    protected $appends = [
        'inventory', 'stateFormat'
    ];

    public function getCouponGiveAttribute()
    {
        return collect(json_decode($this->attributes['couponGive'],true))->map(function ($coupon) {
            $coupon['coupon'] = CouponCoupon::find($coupon['id']);
            return $coupon;
        });
    }

    public function getInventoryAttribute()
    {
        $key = "wordCouponInventory:{$this->id}";
        $issusKey = "wordCouponInventoryUse:{$this->id}";
        $issusCount = Cache::get($issusKey, 0);
        $inventory = $this->inventoryLimit['inventory'] ?? 0;
        if (!Cache::has($key)) {
            Cache::set($key, $inventory - $issusCount);
        }
        return Cache::get($key);
    }

    public function subInventory($num = 1)
    {
        $key = "wordCouponInventory:{$this->id}";
        $issusKey = "wordCouponInventoryUse:{$this->id}";
        $count = Cache::get($key);
        $issusCount = Cache::get($issusKey, 0);
        Cache::set($key, ($count - $num));
        Cache::set($issusKey, ($issusCount + $num));
        return true;
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
