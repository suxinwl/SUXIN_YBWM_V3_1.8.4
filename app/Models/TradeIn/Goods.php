<?php

namespace App\Models\TradeIn;

use App\Models\BaseModel;
use App\Models\Store\StoreGoodsBase;
use App\Models\Store\StoreGoodsSkuBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class Goods extends BaseModel
{
    use HasFactory;
    protected $table = 'tradein_goods';
    protected $fillable = [
        'uniacid', 'spec', 'price', 'userType', 'name', 'logo', 'discountRule', 'rule', 'activityId', 'type', 'spuId', 'discountType', 'wmDiscount', 'dnDiscount', 'startTime', 'endTime', 'state', 'discountLabel'
    ];
    protected $appends = [
        'discountLabel'
    ];
    protected $casts =  [
        'rule' => 'array',
        'scenario' => 'array',
        'discountRule' => 'array',
    ];
    public function stores()
    {
        return $this->hasMany(Store::class, 'activityId', 'activityId');
    }

    public function getDiscountLabelAttribute()
    {
        return '换购';
    }

    public function storeGoods()
    {
        return $this->hasOne(StoreGoodsSkuBase::class, 'specMd5', 'specMd5');
    }

    public function getActivityPriceAttribute()
    {
        $diningType = Request()->diningType ?? 99;
        $userId = auth('user')->user()->id ?? Request()->userId;
        $user = DB::table('member')->where('id', $userId)->where('mobile', '!=', '')->first();
        if (in_array($diningType, [1, 2])) {
            $scenario = 2;
        } elseif ($diningType == 0) {
            $scenario = 1;
        } elseif (in_array($diningType, [5, 6])) {
            $scenario = 4;
        } elseif ($diningType == 4) {
            $scenario = 3;
        } else {
            return $scenario = 0;
        }
        if ($user) {
            if ($this->userType == 1) {
                if (in_array($diningType, [0, 1, 2])) {
                    return bcmul($this->wmDiscount, 1, 2);
                } elseif (in_array($diningType, [4, 5, 6])) {
                    return bcmul($this->dnDiscount, 1, 2);
                }
            } elseif ($this->userType == 2) {
                $rule = collect($this->discountRule)->where('id', $user->vipId)->first();
                if ($rule) {
                    if (in_array($diningType, [0, 1, 2])) {
                        return  bcmul($rule['wmDiscount'], 1, 2);
                    } elseif (in_array($diningType, [4, 5, 6])) {
                        return  bcmul($rule['dnDiscount'], 1, 2);
                    }
                } else {
                    $discount = null;
                }
            }
        } else {
            $discount = null;
        }
    }
}
