<?php

namespace App\Services;

use App\Jobs\ProfitSharingJob;
use App\Models\Order\Bill;
use App\Models\Order\OrderIndex;
use App\Models\PayTemplate;
use App\Models\StatisticsDay;
use App\Models\StatisticsOrder;
use App\Models\Store\Account;
use App\Models\Store\AccountLog;
use App\Services\Pay\WechatPay;
use App\Traits\ResourceTrait;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StaticService
{
    public static function tongji($orderSn)
    {
        try {
            $orderIndex = OrderIndex::where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                return false;
            }
            if (!in_array($orderIndex->state, [6, 8, 10])) {
                return false;
            }
            if ($orderIndex->substr->state == 8) {
                $orderIndex->state = 8;
                $orderIndex->save();
            }
            Log::error("statisticsData");
            if ($orderIndex->state == 6 || $orderIndex->state == 10) {
                if ($orderIndex->type == 4 && $orderIndex->subOrder->diningType == 4 && $orderIndex->subOrder->payType == 1) {
                    $subOrder = collect($orderIndex->subOrder->subOrder)->where('state', 6)->all();
                    DB::table('order_goods')->where('prentOrderSn', $orderIndex->orderSn)->update([
                        'completionTime' => $orderIndex->completionTime
                    ]);
                    collect($subOrder)->each(function ($order) {
                        DB::table('statistics_order')->where("orderSn", $order->orderSn)->delete();
                        $statisticsOrder = new StatisticsOrder();
                        $statisticsOrder->fill(collect($order->orderIndex)->toArray());
                        $statisticsOrder->fill(collect($order)->toArray());
                        $statisticsOrder->score = $order->orderIndex->score;
                        $statisticsOrder->state = $order->orderIndex->state;
                        $statisticsOrder->payType = $order->orderIndex->payType;
                        $statisticsOrder->mchId = $order->orderIndex->mchId;
                        $statisticsOrder->orderCount = 1;
                        $statisticsOrder->payTime = $order->payTime;
                        $statisticsOrder->thirdNo = $order->orderIndex->thirdNo;
                        $statisticsOrder->day = $order->completionDay;
                        $statisticsOrder->h = $order->completionH;
                        $statisticsOrder->sellMoney = $order->sellMoney;
                        $statisticsOrder->payType = $order->orderIndex->payType;
                        $statisticsOrder->storedValueMoney = 0;
                        $statisticsOrder->mchId = $order->orderIndex->mchId;
                        $statisticsOrder->money = $order->service_money?bcadd($order->money,$order->service_money,2):$order->money;
                        $statisticsOrder->created_at =  $order->completionTime ?? date("Y-m-d H:i:s", time());
                        $statisticsOrder->discountMoney = bcsub(bcadd($statisticsOrder->sellMoney,$order->service_money,2), $statisticsOrder->money, 2);
                        $statisticsOrder->save();
                        DB::table('statistics_order')->where("orderSn", $statisticsOrder->orderSn)->update([
                            'orderCount' => 1,
                            'created_at' => $order->completionTime
                        ]);
                        DB::table('member')->where('id', $statisticsOrder->userId)->increment('payOrder', 1);
                    });
                } else {
                    $statisticsOrder = StatisticsOrder::where('orderSn', $orderSn)->delete();
                    $statisticsOrder = new StatisticsOrder();
                    $statisticsOrder->fill(collect($orderIndex->subOrder)->toArray());
                    $statisticsOrder->fill(collect($orderIndex)->toArray());
                    $statisticsOrder->score = $orderIndex->score;
                    $statisticsOrder->state = $orderIndex->state;
                    $statisticsOrder->payType = $orderIndex->payType;
                    $statisticsOrder->costomPayId = $orderIndex->costomPayId;
                    $statisticsOrder->mchId = $orderIndex->mchId;
                    $statisticsOrder->orderCount = 1;
                    $statisticsOrder->payTime = $orderIndex->subOrder->payTime ?? $orderIndex->subOrder->updated_at;
                    $statisticsOrder->thirdNo = $orderIndex->thirdNo;
                    $statisticsOrder->day = $orderIndex->subOrder->completionDay;
                    $statisticsOrder->h = $orderIndex->subOrder->completionH;
                    $statisticsOrder->sellMoney = !in_array($statisticsOrder->type, [1, 3, 4]) ? $orderIndex->subOrder->money  : $orderIndex->subOrder->sellMoney;
                    $statisticsOrder->storedValueMoney = $orderIndex->type == 2 ?  $orderIndex->subOrder->money : 0;
                    $statisticsOrder->mchId = $orderIndex->mchId;
                    $statisticsOrder->money = $statisticsOrder->money;
                    $statisticsOrder->created_at =  $orderIndex->subOrder->completionTime ?? date("Y-m-d H:i:s", time());
                    $statisticsOrder->discountMoney = bcsub($statisticsOrder->sellMoney, $statisticsOrder->money, 2);
                    $statisticsOrder->save();
                    DB::table('statistics_order')->where("orderSn", $statisticsOrder->orderSn)->update([
                        'orderCount' => 1,
                        'created_at' => $orderIndex->subOrder->completionTime ?? $orderIndex->subOrder->updated_at
                    ]);
                    DB::table('member')->where('id', $statisticsOrder->userId)->increment('payOrder', 1);
                    if ($orderIndex->type == 4) {
                        DB::table('order_goods')->where('orderSn', $orderIndex->orderSn)->orWhere('prentOrderSn', $orderIndex->orderSn)->update([
                            'completionTime' => $orderIndex->subOrder->completionTime
                        ]);
                    }
                    if ($orderIndex->type == 1) {
                        DB::table('order_goods')->where('orderSn', $orderIndex->orderSn)->update([
                            'completionTime' => $orderIndex->subOrder->completionTime
                        ]);
                    }
                }
                $orderIndex->isTj = 1;
                $orderIndex->save();
            } elseif ($orderIndex->state == 8) {
                if ($orderIndex->type == 4 && $orderIndex->subOrder->diningType == 4 && $orderIndex->subOrder->payType == 1) {
                    $subOrder = collect($orderIndex->subOrder->subOrder)->where('state', 8)->all();
                    collect($subOrder)->each(function ($order) use ($orderIndex) {
                        DB::table('statistics_order')->where("orderSn", $order->orderSn)->delete();
                        // $statisticsOrder = StatisticsOrder::where('orderSn', $orderSn)->first();
                        $statisticsOrder = new StatisticsOrder();
                        $statisticsOrder->fill(collect($orderIndex)->toArray());
                        $statisticsOrder->fill(collect($orderIndex->subOrder)->toArray());
                        $statisticsOrder->orderSn = $order->orderSn;
                        $statisticsOrder->score = $orderIndex->score;
                        $statisticsOrder->state = $orderIndex->state;
                        $statisticsOrder->payType = $orderIndex->payType;
                        $statisticsOrder->costomPayId = $orderIndex->costomPayId;
                        $statisticsOrder->mchId = $orderIndex->mchId;
                        $statisticsOrder->payTime = $orderIndex->subOrder->payTime ?? $orderIndex->subOrder->updated_at;
                        $statisticsOrder->thirdNo = $orderIndex->thirdNo;
                        $statisticsOrder->day = $orderIndex->subOrder->completionDay;
                        $statisticsOrder->h = $orderIndex->subOrder->completionH;
                        $statisticsOrder->discountMoney = 0;
                        $statisticsOrder->deliveryMoney = 0;
                        $statisticsOrder->boxMoney = 0;
                        $statisticsOrder->tableMoney = 0;
                        $statisticsOrder->orderCount = 0;
                        $statisticsOrder->sellMoney = 0;
                        $statisticsOrder->storedValueMoney = 0;
                        $statisticsOrder->money = 0;
                        $statisticsOrder->sellMoney = 0;
                        $statisticsOrder->refundOrder = 1;
                        $statisticsOrder->refundMoney = $order->refundMoney;
                        $statisticsOrder->created_at =  $orderIndex->subOrder->completionTime ?? date("Y-m-d H:i:s", time());
                        $statisticsOrder->save();
                        DB::table('statistics_order')->where("orderSn", $statisticsOrder->orderSn)->update([
                            'created_at' => $orderIndex->subOrder->updated_at
                        ]);
                        DB::table('member')->where('id', $statisticsOrder->userId)->where('payOrder', ">", 0)->decrement('payOrder', 1);
                    });
                } else {
                    $statisticsOrder = StatisticsOrder::where('orderSn', $orderSn)->delete();
                    $statisticsOrder = new StatisticsOrder();
                    $statisticsOrder->fill(collect($orderIndex->subOrder)->toArray());
                    $statisticsOrder->fill(collect($orderIndex)->toArray());
                    $statisticsOrder->score = $orderIndex->score;
                    $statisticsOrder->state = $orderIndex->state;
                    $statisticsOrder->payType = $orderIndex->payType;
                    $statisticsOrder->mchId = $orderIndex->mchId;
                    $statisticsOrder->payTime = $orderIndex->subOrder->payTime ?? $orderIndex->subOrder->updated_at;
                    $statisticsOrder->thirdNo = $orderIndex->thirdNo;
                    $statisticsOrder->day = $orderIndex->subOrder->completionDay;
                    $statisticsOrder->h = $orderIndex->subOrder->completionH;
                    $statisticsOrder->discountMoney = 0;
                    $statisticsOrder->deliveryMoney = 0;
                    $statisticsOrder->boxMoney = 0;
                    $statisticsOrder->tableMoney = 0;
                    $statisticsOrder->orderCount = 0;
                    $statisticsOrder->sellMoney = 0;
                    $statisticsOrder->storedValueMoney = 0;
                    $statisticsOrder->money = 0;
                    $statisticsOrder->sellMoney = 0;
                    $statisticsOrder->refundOrder = 1;
                    $statisticsOrder->refundMoney = $orderIndex->subOrder->refundMoney;
                    $statisticsOrder->created_at =  $orderIndex->subOrder->completionTime ?? date("Y-m-d H:i:s", time());
                    $statisticsOrder->save();
                    DB::table('statistics_order')->where("orderSn", $statisticsOrder->orderSn)->update([
                        'created_at' => $orderIndex->subOrder->updated_at
                    ]);
                    DB::table('member')->where('id', $statisticsOrder->userId)->where('payOrder', ">", 0)->decrement('payOrder', 1);
                }
                $orderIndex->isTj = 1;
                $orderIndex->save();
            }
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '-' . $e->getLine() . $e->getFile());
            return false;
        }
    }
}
