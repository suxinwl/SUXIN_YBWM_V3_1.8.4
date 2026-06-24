<?php

namespace App\Models\Coupon;

use App\Models\BaseModel;
use App\Models\GoodsSpu;
use App\Models\Store;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Coupon extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'coupon';
    protected $fillable = ['userType','groupId','tags','startTime','storeId','verificationSwitch', 'regiftSwitch', 'regiftTitle', 'regiftImage', 'endTime', 'uniacid', 'type', 'sn', 'name', 'couponType', 'channel', 'notes', 'state', 'sort', 'scenario', 'rule', 'startSwitch', 'startSwitch', 'startMoney', 'threshold', 'goodsType', 'goodsIds', 'storeType', 'storeIds', 'period', 'inventoryLimit', 'body'];

    protected $casts =  [
        'scenario' => 'array',
        'rule' => 'array',
        'threshold' => 'array',
        'goodsIds' => 'array',
        'storeIds' => 'array',
        'period' => 'array',
        'inventoryLimit' => 'array',
        'tags' => 'array',
        'groupId' => 'array',
    ];
    protected $appends = [
        'typeFormat', 'timeArr', 'stateFormat', 'startTimeFormat', 'endTimeFormat', 'inventory', 'goods'
    ];

    protected $withCount = [
        'receives', 'receivesUse'
    ];

    protected $attributes = [
        'sort' => 0,
        'state' => 1,
        'startMoney' => 0
    ];

    public function getStateFormatAttribute()
    {
        if ($this->startTime == null) {
            return "进行中";
        }
        if (time() < strtotime($this->startTime)) {
            return "未开始";
        } elseif (time() >= strtotime($this->startTime) && time() <= strtotime($this->endTime)) {
            return "进行中";
        } else {
            return "已结束";
        }
    }
    public function getTimeArrAttribute()
    {
        if ($this->period['type'] == 1) {
            $startTime = strtotime($this->period['timeArr']['startTime']);
            $endTime = strtotime($this->period['timeArr']['endTime']);
            return [
                'startTimeFormat' => date("Y-m-d", $startTime),
                'startTime' => date("Y-m-d H:i:s", $startTime),
                'endTime' => date("Y-m-d H:i:s", $endTime),
                'endTimeFormat' => date("Y-m-d", $endTime)
            ];
        }
        if ($this->period['type'] == 2) {
            $startTime =  $this->period['day']['type'] == 1 ? time() : strtotime(date("Y-m-d", strtotime("+1 day")));
            $endTime = $startTime + 3600 * 24 * intval($this->period['day']['value']) - 1;
            return [
                'startTimeFormat' => date("Y-m-d", $startTime),
                'startTime' => date("Y-m-d H:i:s", $startTime),
                'endTime' => date("Y-m-d H:i:s", $endTime),
                'endTimeFormat' => date("Y-m-d", $endTime)
            ];
        }
        if ($this->period['type'] == 3) {
            $startTime =  $this->period['hours']['type'] == 1 ? time() : strtotime(date("Y-m-d", strtotime("+1 day")));
            $endTime = $startTime + 3600 * intval($this->period['hours']['value']);
            if (date("d", $endTime) != date("d", $startTime)) {
                $endTime = strtotime(date("Y-m-d 23:59:59", $startTime));
            }
            return [
                'startTimeFormat' => date("Y-m-d", $startTime),
                'startTime' => date("Y-m-d H:i:s", $startTime),
                'endTime' => date("Y-m-d H:i:s", $endTime),
                'endTimeFormat' => date("Y-m-d", $endTime)
            ];
        }
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->couponType = 1;
            if ($model->type == 4) {
                $model->couponType = 2;
            }
            if ($model->type == 3) {
                $model->goodsType = 2;
            }
            $model->sn = empty($model->sn) ? CouponRandInt(10) : $model->sn;
        });
        static::saved(function ($model) {
            $key = "couponInventory:{$model->id}";
            Cache::delete($key);
        });
    }

    public function getInventoryAttribute()
    {
        $key = "couponInventory:{$this->id}";
        $issusKey = "couponInventoryUse:{$this->id}";
        $issusCount = Cache::get($issusKey, 0);
        $inventory = $this->inventoryLimit['inventory'] ?? 0;
        if (!Cache::has($key)) {
            Cache::set($key, $inventory - $issusCount);
        }
        return Cache::get($key);
    }

    public function subInventory($num = 1)
    {
        $key = "couponInventory:{$this->id}";
        $issusKey = "couponInventoryUse:{$this->id}";
        $count = Cache::get($key);
        $issusCount = Cache::get($issusKey, 0);
        Cache::set($key, ($count - $num));
        Cache::set($issusKey, ($issusCount + $num));
        return true;
    }

    public function getUserDayLimitAttribute()
    {
        $user = auth('user')->user();
        $dayLimitKey = "coupon:userDaylimit:{$this->id}" . date("Ymd") . ":{$user->id}";
        if (!Cache::has($dayLimitKey)) {
            Cache::set($dayLimitKey, 0);
        }
        return Cache::get($dayLimitKey);
    }

    public function getUserLimitAttribute()
    {
        $user = auth('user')->user();
        $limitKey = "coupon:userlimit:{$this->id}{$user->id}";
        if (!Cache::has($limitKey)) {
            Cache::set($limitKey, 0);
        }
        return Cache::get($limitKey);
    }

    public function getStartTimeFormatAttribute()
    {
        return date("Y-m-d", strtotime($this->startTime));
    }

    public function getendTimeFormatAttribute()
    {
        return date("Y-m-d", strtotime($this->endTime));
    }

    public function receives()
    {
        return $this->hasMany(MemberCoupon::class, 'couponId', 'id');
    }

    public function receivesUse()
    {
        return $this->hasMany(MemberCoupon::class, 'couponId', 'id')->where('state', 2);
    }

    public function getGoodsAttribute()
    {
        if ($this->goodsIds) {
            return DB::table('goods_spu')->select(['id', 'name', 'logo'])->whereIn('id', $this->goodsIds)->get();
        }
        return null;
    }

    public function getTypeFormatAttribute()
    {
        $data = [
            1 => '代金券',
            2 => '折扣券',
            3 => '兑换券',
            4 => '配送券',
            5 => '固定价格券'
        ];
        return $data[$this->type];
    }
}
