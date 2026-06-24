<?php

namespace App\Models\GoodsSearch\Store;

use App\Models\BaseModel;
use App\Models\GoodsActivity\Goods;
use App\Models\GoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class TakeoutSku extends BaseModel
{
    protected $table = 'store_goods_sku';
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    protected $casts =  [
        'specName' => 'array',
    ];
    protected $appends = [
        'discount'
    ];

    public $_discount;

    public function getDiscountAttribute()
    {
        if (!$this->_discount) {
            $storeId = $this->storeId;
            $userId = auth('user')->user()->id;
            $diningType = Request()->diningType ?? 0;
            $discount = Goods::where('uniacid', $this->uniacid)
                ->where('specMd5', $this->specMd5)
                ->where('state', 1)
                ->where("startTime", "<=", Carbon::now()->toDateTimeString())
                ->where("endTime", ">=", Carbon::now()->toDateTimeString())
                ->when($diningType || $diningType == 0, function ($q) use ($diningType) {
                    if (in_array($diningType, [1, 2])) {
                        return $q->where('scenario', 'like', "2");
                    } elseif ($diningType == 0) {
                        return $q->where('scenario', 'like', "1");
                    } elseif (in_array($diningType, [5, 6])) {
                        return $q->where('scenario', 'like', "4");
                    } elseif ($diningType == 4) {
                        return $q->where('scenario', 'like', "3");
                    } else {
                        return $q->where('id', 0);
                    }
                })
                ->where(function ($q) use ($storeId) {
                    return $q->whereDoesntHave('stores')->orWhere(function ($q) use ($storeId) {
                        return $q->whereHas('stores', function ($q) use ($storeId) {
                            return $q->where('storeId', $storeId);
                        });
                    });
                })
                ->orderBy('type', 'desc')
                ->first();
            if ($discount) {
                $price = $this->price;
                if ($discount->type == 6) {
                    if ($discount->discountType == 1) {
                        $price = bcmul(bcdiv($price, 100, 4), $discount->wmDiscount * 10, 2);
                    }
                    if ($discount->discountType == 2) {
                        $price = bcsub($price, $discount->wmDiscount, 2);
                        if ($price < 0) {
                            $price = 0;
                        }
                    }
                    if ($discount->discountType == 3) {
                        $price = $discount->wmDiscount;
                        if ($price < 0) {
                            $price = 0;
                        }
                    }
                    $discount = collect($discount)->toArray();
                    $discount['price'] = $price;
                    $discount['linePrice'] = $this->price;
                } else {
                    $key = "goodsDiscount:{$discount->id}:{$userId}";
                    $dayKey = "goodsDiscount:{$discount->activityId}:{$userId}:" . Carbon::now()->toDateString();
                    $keyLimit = Cache::get($key, 0);
                    $dayLimit = Cache::get($dayKey, 0);
                    if (
                        ($discount->rule['userLimit']['switch'] == 1 && $discount->rule['userLimit']['value'] <= $keyLimit) ||
                        ($discount->rule['dayLimit']['switch'] == 1 && $discount->rule['dayLimit']['value'] <= $dayLimit)
                    ) {
                        $discount = null;
                    } else {
                        $discount = collect($discount)->toArray();
                    }
                }
                $this->_discount  = $discount;
            }
        }
        return $this->_discount;
    }
}
