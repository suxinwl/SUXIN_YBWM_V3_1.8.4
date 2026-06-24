<?php

namespace App\Models\Coupon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Activity extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'coupon_activity';
    protected $fillable = [
        'bodyImg', 'startTime', 'endTime', 'uniacid', 'name', 'logo', 'notes', 'notes', 'sort', 'inventoryLimit', 'body', 'couponIds', 'storeId'
    ];

    protected $appends = [
        'inventory', 'couponList', 'stateFormat'
    ];

    protected $casts =  [
        'inventoryLimit' => 'array',
        'couponIds' => 'array'
    ];

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

    public function getInventoryAttribute()
    {
        $key = "activityCouponInventory:{$this->id}";
        $issusKey = "activityCouponInventoryUse:{$this->id}";
        $issusCount = Cache::get($issusKey, 0);
        $inventory = $this->inventoryLimit['inventory'] ?? 0;
        if (!Cache::has($key)) {
            Cache::set($key, $inventory - $issusCount);
        }
        return Cache::get($key);
    }

    public function subInventory($num = 1)
    {
        $key = "activityCouponInventory:{$this->id}";
        $issusKey = "activityCouponInventoryUse:{$this->id}";
        $count = Cache::get($key);
        $issusCount = Cache::get($issusKey, 0);
        Cache::set($key, ($count - $num));
        Cache::set($issusKey, ($issusCount + $num));
        return true;
    }

    public function getUserDayLimitAttribute()
    {
        $user = auth('user')->user();
        $dayLimitKey = "activityCoupon:userDaylimit:{$this->id}" . date("Ymd") . ":{$user->id}";
        if (!Cache::has($dayLimitKey)) {
            Cache::set($dayLimitKey, 0);
        }
        return Cache::get($dayLimitKey);
    }

    public function getUserLimitAttribute()
    {
        $user = auth('user')->user();
        $limitKey = "activityCoupon:userlimit:{$this->id}{$user->id}";
        if (!Cache::has($limitKey)) {
            Cache::set($limitKey, 0);
        }
        return Cache::get($limitKey);
    }

    public function getCouponListAttribute()
    {
        $ids = collect($this->couponIds)->pluck('id')->all();
        if ($ids) {
            return Coupon::where('uniacid', $this->uniacid)->whereIn('id', $ids)->get();
        }
        return null;
    }
}
