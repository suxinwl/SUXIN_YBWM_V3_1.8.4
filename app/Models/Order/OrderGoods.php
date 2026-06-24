<?php

namespace App\Models\Order;

use App\Jobs\Order\OrderGoodsJob;
use App\Models\BaseModel;
use App\Models\GoodsCat;
use App\Models\GoodsSpu;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class OrderGoods extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'order_goods';
    protected $guarded = ['goods', 'tableId', 'id'];
    protected $casts =  [
        'address' => 'array',
        'attrData' => 'array',
        'setMealData' => 'array'
    ];
    protected $primaryKey = 'spuId';
    public function order()
    {
        return $this->hasOne(TakeOutOrder::class, "orderSn", 'orderSn');
    }

    public function spu()
    {
        return $this->hasOne(GoodsSpu::class, "id", 'spuId');
    }


    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            try {
                if (!$model->exists) {
                    // if (in_array($model->scene, [1, 2])) {
                    //     $userLimitKey = "userGoods:{$model->spuId}:" . $model->userId;
                    //     $userDayLimitKey = "userGoods:" . date("Y-m-d") . ":{$model->spuId}:" . $model->userId;
                    //     Cache::increment($userLimitKey, $model->num);
                    //     Cache::increment($userDayLimitKey, $model->num);
                    // }
                    // Cache::increment("storeGoods:{$model->storeId}:{$model->spuId}", $model->num);
                    // GoodsSpu::where('id', $model->spuId)->increment('sales', $model->num);
                    // StoreGoodsSku::where('surplusInventory', ">", 0)->where('storeId', $model->storeId)->where("specMd5", $model->specMd5)->decrement('surplusInventory', $model->num);
                    // if ($model->activityId > 0) {
                    //     $key = "goodsDiscount:{$model->activityId}:{$model->userId}";
                    //     $dayKey = "goodsDiscount:{$model->activityId}:{$modsl->userId}:" . Carbon::now()->toDateString();
                    //     if (!Cache::has("$model->orderSn:" . $key)) {
                    //         Cache::increment($key, 1);
                    //     }
                    //     if (!Cache::has("$model->orderSn:" . $dayKey)) {
                    //         Cache::increment($dayKey, 1);
                    //     }
                    // }
                    StoreGoodsSku::where('surplusInventory', ">", 0)->where('storeId', $model->storeId)->where("specMd5", $model->specMd5)->decrement('surplusInventory', $model->num);
                    dispatch(new OrderGoodsJob($model, 'add'));
                    return true;
                }
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });

        // static::updating(function ($model) {
        //     if ($model->state == 6) {
        //         GoodsSpu::where('id', $model->spuId)->increment('sales', $model->num);
        //     } elseif ($model->getOriginal('state') == 6 && $model->state == 8) {
        //         GoodsSpu::where('id', $model->spuId)->decrement('sales', $model->num);
        //     }
        // });
        static::deleted(function ($model) {
            try {
                // if (in_array($model->scene, [1, 2])) {
                //     $userLimitKey = "userGoods:{$model->spuId}:" . $model->userId;
                //     $userDayLimitKey = "userGoods:" . date("Y-m-d") . ":{$model->spuId}:" . $model->userId;
                //     Cache::decrement($userLimitKey, $model->num);
                //     Cache::decrement($userDayLimitKey, $model->num);
                // }
                // Cache::decrement("storeGoods:{$model->storeId}:{$model->spuId}", $model->num);
                // GoodsSpu::where('sales', '>', 0)->where('id', $model->spuId)->decrement('sales', $model->num);
                // StoreGoodsSku::where('storeId', $model->storeId)->where("specMd5", $model->specMd5)->increment('surplusInventory', $model->num);
                // if ($model->activityId > 0) {
                //     $key = "goodsDiscount:{$model->activityId}:{$model->userId}";
                //     $dayKey = "goodsDiscount:{$model->activityId}:{$model->userId}:" . Carbon::now()->toDateString();
                //     if (Cache::has("$model->orderSn:" . $key)) {
                //         Cache::decrement($key, 1);
                //         if (Cache::get($key) == 0) {
                //             Cache::delete("$model->orderSn:" . $key);
                //         }
                //     }
                //     if (Cache::has("$model->orderSn:" . $dayKey)) {
                //         Cache::decrement($dayKey, 1);
                //         if (Cache::get($dayKey) == 0) {
                //             Cache::delete("$model->orderSn:" . $dayKey);
                //         }
                //     }
                // }
                if ($model->state == 6) {
                    GoodsSpu::where('id', $model->spuId)->decrement('sales', $model->num);
                }
                StoreGoodsSku::where('storeId', $model->storeId)->where("specMd5", $model->specMd5)->increment('surplusInventory', $model->num);
                dispatch(new OrderGoodsJob($model, 'refund'));
                return true;
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });
    }
    public function goodsCat()
    {
        return $this->belongsToMany(GoodsCat::class, 'spu_catids', 'spuId', 'catId', 'spuId');
    }

    public function getAttrDataAttribute()
    {
        if (!empty($this->attributes['attrData'])) {
            return json_decode($this->attributes['attrData'], true);
        } else {
            return [];
        }
    }

    public function getNameFormatAttribute()
    {
        return $this->name . " x" . $this->num;
    }

    public function getDiscountLabelAttribute()
    {
        if (!empty($this->attributes['discountLabel'])) {
            return $this->attributes['discountLabel'];
        } else {
            return $this->state == 8 ? "退" : null;
        }
    }
}
