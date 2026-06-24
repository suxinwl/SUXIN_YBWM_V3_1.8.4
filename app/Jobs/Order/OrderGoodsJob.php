<?php

namespace App\Jobs\Order;

use App\Models\GoodsSpu;
use App\Models\Order\OrderGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mail;

class OrderGoodsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $goods;
    public $type;
    public function __construct(OrderGoods $goods, $type)
    {
        $this->goods = $goods;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $model = $this->goods;
        $type = $this->type;
        Log::error($model);
        if ($type == "add") {
            if (in_array($model->scene, [1, 2])) {
                $userLimitKey = "userGoods:{$model->spuId}:" . $model->userId;
                $userDayLimitKey = "userGoods:" . Carbon::now()->toDateString() . ":{$model->spuId}:" . $model->userId;
                Cache::increment($userLimitKey, $model->num);
                Cache::increment($userDayLimitKey, $model->num);
            }
            if ($model->activityId > 0) {
                $key = "goodsDiscount:{$model->activityId}:{$model->userId}";
                $dayKey = "goodsDiscount:{$model->activityId}:{$model->userId}:" . Carbon::now()->toDateString();
                if (!Cache::has("$model->orderSn:" . $key)) {
                    Cache::increment($key, 1);
                }
                if (!Cache::has("$model->orderSn:" . $dayKey)) {
                    Cache::increment($dayKey, 1);
                }
            }
        } elseif ($type == "refund") {
            if (in_array($model->scene, [1, 2])) {
                $userLimitKey = "userGoods:{$model->spuId}:" . $model->userId;
                $userDayLimitKey = "userGoods:" . Carbon::now()->toDateString() . ":{$model->spuId}:" . $model->userId;
                Cache::decrement($userLimitKey, $model->num);
                Cache::decrement($userDayLimitKey, $model->num);
            }
            Cache::decrement("storeGoods:{$model->storeId}:{$model->spuId}", $model->num);
            if ($model->activityId > 0) {
                $key = "goodsDiscount:{$model->activityId}:{$model->userId}";
                $dayKey = "goodsDiscount:{$model->activityId}:{$model->userId}:" . Carbon::now()->toDateString();
                if (Cache::has("$model->orderSn:" . $key)) {
                    Cache::decrement($key, 1);
                    if (Cache::get($key) == 0) {
                        Cache::delete("$model->orderSn:" . $key);
                    }
                }
                if (Cache::has("$model->orderSn:" . $dayKey)) {
                    Cache::decrement($dayKey, 1);
                    if (Cache::get($dayKey) == 0) {
                        Cache::delete("$model->orderSn:" . $dayKey);
                    }
                }
            }
        }
    }
}
