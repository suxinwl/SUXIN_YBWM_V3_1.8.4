<?php

namespace App\Services;
use App\Models\StorePartnerOrder;
use App\Events\OrderCollectEvent;
use App\Events\OrderMessageEvent;
use App\Events\PartyBEvent;
use App\Events\PayGiftEvent;
use App\Events\StoreMessageEvent;
use App\Jobs\OrderPrintJob;
use App\Jobs\OrderStatisticsJob;
use App\Models\BulkPackage;
use App\Models\Coupon\MemberCoupon;
use App\Models\GoodsSpu;
use App\Models\Handover\Handover;
use App\Models\InStore\Checkout;
use App\Models\InStore\Order\Order;
use App\Models\Member;
use App\Models\MemberAccountLog;
use App\Models\Order\OrderIndex;
use App\Models\Order\TakeOutOrder;
use App\Models\Order\TakeScreen;
use App\Models\OrderCollect\OrderCollect;
use App\Models\OrderLog;
use App\Models\PersionPayOrder;
use App\Models\Printer;
use App\Models\PrinterLog;
use App\Models\QueuingUp;
use App\Models\RefundOrder;
use App\Models\StatisticsDay;
use App\Models\StoredValueOrder;
use App\Models\Tables\Area;
use App\Models\Tables\Table;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\Kernel\Exceptions\Exception;
use App\Services\Print\SuodiQrcode;
use Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

use App\Models\MemberAccount;
use App\Models\OldWithNew\PartyA;
use App\Models\OldWithNew\PartyB;
use App\Models\Order\Discount;
use App\Models\Order\OrderGoods;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Cache as Caches;
use App\Services\Print\FeieLabelContent;
use App\Services\Print\XinyeLabelContent;
use App\Models\PartnerOrder;

class InStoreOrderService extends BaseService
{
    /**
     * 关闭订单
     */
    public  static function close($orderId, $log = '', $adminId = 0)
    {
        try {
            DB::beginTransaction();
            $order = Order::where('id', $orderId)
                ->whereIn('state', [1, 2])
                ->first();
            if (empty($order)) {
                throw new BadRequestException('数据不存在');
            }
            if ($order->subOrder->toArray() && $order->diningType == 4) {
                $order = collect($order->subOrder)->whereIn('state', [1, 2])->first();
            }
            if ($order->isPay == 1) {
                $refundMoney = $order->payMoney;
                $orderData = [
                    'takeOutNo' => $order->orderIndex->orderSn,
                    'openid' => $order->orderIndex->payer,
                    'transaction_id' => $order->orderIndex->thirdNo,
                    'total_amount' => $order->money,
                    'refund_amount' => $refundMoney,
                ];
                if ($order->orderIndex->payChannel == 1) {
                    if (!StoreAccountService::refundDirectly($order->orderIndex->storeId, $refundMoney, $adminId, $order->orderIndex->takeOutNo)) {
                        DB::rollBack();
                        throw  new BadRequestException('退款失败');
                    }
                }
                $refundOrder = RefundOrder::where('takeOutNo', $order->orderSn)->first();
                if (empty($refundOrder)) {
                    $res = PayService::refund($orderData, $order->orderIndex->uniacid, $order->orderIndex->payTempId);
                } else {
                    $res = true;
                }
                $order->state = 8;
            } else {
                $order->state = 0;
            }
            $order->save();
            if ($order->statisticsData && $order->isPay == 1) {
                StatisticsDay::where(function ($q) use ($order) {
                    return $q->where(function ($q) use ($order) {
                        return $q->where("uniacid", $order->uniacid)->where("storeId", 0);
                    })->orWhere(function ($q) use ($order) {
                        return $q->where("uniacid", $order->uniacid)->where("storeId", $order->storeId);
                    });
                })->where("day", date("Y-m-d", time()))->update($order->statisticsData);
            }
            $order->setLog($log);
            $order->orderIndex->isShow = 0;
            $order->orderIndex->state = 0;
            $order->orderIndex->save();
            collect($order->goods)->map(function ($goods) {
                $goods->delete();
            });
            if ($order->couponId) {
                MemberCoupon::where('id', $order->couponId)->update([
                    'state' => 1,
                    'orderId' => 0,
                    'updated_at' => null
                ]);
            }
            if ($order->diningType == 4) {
                $subOrder = Order::where('state', 3)->where('prentOrderSn', $order->prentOrderSn)->first();
                if ($subOrder) {
                    $order->perentOrder->state = 3;
                    $order->perentOrder->save();
                    $order->perentOrder->changeData();
                } else {
                    if ($order->perentOrder) {
                        $order->perentOrder->state = $order->state;
                        $order->perentOrder->orderIndex->state = 0;
                        $order->perentOrder->save();
                        $order->perentOrder->orderIndex->save();
                        $order->perentOrder->table->fill(['orderSn' => null, 'people' => 0, 'state' => 0, 'expiredTime' => null]);
                        $order->perentOrder->table->save();
                        DB::table("table")->where('id',  $order->tableId)->update([
                            'state' => 0,
                            'people' => 0,
                            'orderSn' => null,
                            'expiredTime' => null,
                            'scan' => 0,
                            "openTime" => null
                        ]);
                    }
                }
            }
            //dispatch(new OrderStatisticsJob($order->orderSn));
            DB::commit();
            StaticService::tongji($order->orderSn);
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }


    /**
     * 退款
     */
    public  static function refund($orderId, $adminId = 0, $notes = '', $log = null)
    {
        DB::beginTransaction();
        try {
            $inStoreOrder = Order::where('id', $orderId)->orWhere('orderSn', $orderId)
                ->whereIn('state', [3, 4, 5, 6, 7])
                ->first();
            if (empty($inStoreOrder)) {
                throw new BadRequestException('数据不存在');
            }
            if (!$inStoreOrder->subOrder->isEmpty()) {
                $subOrder = $inStoreOrder->subOrder;
                $inStoreOrder->orderIndex->state = 0;
                $inStoreOrder->orderIndex->save();
                $inStoreOrder->table->fill(['orderSn' => null, 'people' => 0, 'state' => 0, 'expiredTime' => null]);
                $inStoreOrder->table->save();
                $refundMoney = $inStoreOrder->payMoney;
                //分销订单撤回
                // file_put_contents('PartnerOrder.log','分销订单撤回'.PHP_EOL,FILE_APPEND);
                $partners  = PartnerOrder::where('orderSn', $inStoreOrder->orderSn)->get();
                if ($partners) {
                    foreach ($partners as $key => $partnerOrder) {
                        MemberAccountService::subCanWithdrawalAmount($partnerOrder->partnerId, $partnerOrder->money, 0, $partnerOrder->orderSn,'订单退款');
                    }
                    PartnerOrder::where('orderSn', $inStoreOrder->orderSn)->update(['isRefund'=>1,'state'=>8]);

                }
                $storePartners  = StorePartnerOrder::where('orderSn', $inStoreOrder->orderSn)->get();
                if($storePartners){
                    StorePartnerOrder::where('orderSn', $inStoreOrder->orderSn)->update(['isRefund'=>1,'state'=>8]);
                    foreach ($storePartners as $key => $partnerOrder) {
                        StoreAccountService::subCanWithdrawalAmount($partnerOrder->partnerId, $partnerOrder->money, 0, $partnerOrder->orderSn,$partnerOrder->type);
                    }
                }

                if ($inStoreOrder->payType == 2 && $inStoreOrder->isPay == 1) {
                    if ($inStoreOrder->orderIndex->payType == 6 || $inStoreOrder->orderIndex->payType > 100) {
                        $res = true;
                    } else {
                        $orderData = [
                            'takeOutNo' => $inStoreOrder->orderSn,
                            'openid' => $inStoreOrder->orderIndex->payer,
                            'transaction_id' => $inStoreOrder->orderIndex->thirdNo,
                            'total_amount' => $inStoreOrder->money,
                            'refund_amount' => $inStoreOrder->money,
                        ];
                        $refundOrder = RefundOrder::where('takeOutNo', $inStoreOrder->orderSn)->first();
                        if (empty($refundOrder)) {
                            $res = PayService::refund($orderData, $inStoreOrder->orderIndex->uniacid, $inStoreOrder->orderIndex->payTempId);
                        } else {
                            $res = true;
                        }
                        if (!$res) {
                            throw  new BadRequestException('退款失败');
                        }
                        if ($inStoreOrder->state == 7) {
                            if ($inStoreOrder->orderIndex->payChannel == 1) {
                                if (!StoreAccountService::refund($inStoreOrder->orderIndex->storeId, $refundMoney, $adminId, $inStoreOrder->orderIndex->takeOutNo)) {
                                    DB::rollBack();
                                    throw  new BadRequestException('申请退款失败');
                                }
                            }
                        } else {
                            if ($inStoreOrder->orderIndex->payChannel == 1) {
                                if (!StoreAccountService::refundDirectly($inStoreOrder->storeId, $refundMoney, $inStoreOrder->userId, $inStoreOrder->takeOutNo)) {
                                    DB::rollBack();
                                    throw  new BadRequestException('申请退款失败');
                                }
                            }
                        }
                    }
                    $inStoreOrder->refundMoney= $refundMoney;
                    $inStoreOrder->state = 8;
                    if ($inStoreOrder->statisticsData) {
                        StatisticsDay::where(function ($q) use ($inStoreOrder) {
                            return $q->where(function ($q) use ($inStoreOrder) {
                                return $q->where("uniacid", $inStoreOrder->uniacid)->where("storeId", 0);
                            })->orWhere(function ($q) use ($inStoreOrder) {
                                return $q->where("uniacid", $inStoreOrder->uniacid)->where("storeId", $inStoreOrder->storeId);
                            });
                        })->where("day", $inStoreOrder->completionDay)->update($inStoreOrder->statisticsData);
                    }
                }
            } else {
                $subOrder[] = $inStoreOrder;
                TakeScreen::where("orderSn", $inStoreOrder->orderSn)->delete();
            }
            collect($subOrder)->whereIn('state', [1, 2, 3, 4, 5, 6, 7])->each(function ($order) use ($adminId, $log, &$inStoreOrder, $notes) {
                if ($order->isPay == 1) {
                    if ($inStoreOrder->payType == 1) {
                        if ($inStoreOrder->orderIndex->payType == 6 || $inStoreOrder->payType > 100) {
                            $res = true;
                        } else {
                            $refundMoney = $order->payMoney;
                            $orderData = [
                                'takeOutNo' => $order->payType == 1 ? $order->orderIndex->orderSn : $order->prentOrderSn,
                                'openid' => $order->orderIndex->payer,
                                'transaction_id' => $order->orderIndex->thirdNo,
                                'total_amount' => $order->money,
                                'refund_amount' => $order->money,
                            ];
                            $refundOrder = RefundOrder::where('takeOutNo', $order->orderSn)->first();
                            if (empty($refundOrder)) {
                                $res = PayService::refund($orderData, $order->orderIndex->uniacid, $order->orderIndex->payTempId);
                            } else {
                                $res = true;
                            }
                        }

                        if (!$res) {
                            throw  new BadRequestException('退款失败');
                        }
                        if ($order->state == 7) {
                            if ($order->orderIndex->payChannel == 1) {
                                if (!StoreAccountService::refund($order->orderIndex->storeId, $refundMoney, $adminId, $order->orderIndex->takeOutNo)) {
                                    DB::rollBack();
                                    throw  new BadRequestException('申请退款失败');
                                }
                            }
                        } else {
                            if (empty($log)) {
                                $order->state = 7;
                                $order->setLog('门店发起退款，原因:' . $notes);
                            }
                            if ($order->orderIndex->payChannel == 1) {
                                if (!StoreAccountService::refundDirectly($order->storeId, $refundMoney, $order->userId, $order->takeOutNo)) {
                                    DB::rollBack();
                                    throw  new BadRequestException('申请退款失败');
                                }
                            }
                        }
                    }

                    if ($order->couponId) {
                        MemberCoupon::where('id', $order->couponId)->update([
                            'state' => 1,
                            'orderId' => 0,
                            'updated_at' => null
                        ]);
                    }
                    if ($order->state == 6 && $order->userId > 0) {
                        /**
                         * 支付有利撤回
                         */
                        if ($order->payGiftId) {
                            Event(new PayGiftEvent($order, 'refund'));
                        }
                        /**
                         * 集点有利撤回
                         */
                        if ($order->collectId>0) {
                            Event(new OrderCollectEvent($order, 'refund'));
                        }
                    }
                    $order->refundMoney = $order->money;
                    $order->state = 8;
                    $inStoreOrder->state = 8;
                    collect($order->goods)->map(function ($goods) {
                        Cache::decrement("storeGoods:{$goods->storeId}:{$goods->spuId}", $goods->num);
                        $goods->delete();
                    });
                    TakeScreen::where("orderSn", $order->orderSn)->delete();
                } else {
                    $order->state = 0;
                }
                if ($order->statisticsData) {
                    StatisticsDay::where(function ($q) use ($order) {
                        return $q->where(function ($q) use ($order) {
                            return $q->where("uniacid", $order->uniacid)->where("storeId", 0);
                        })->orWhere(function ($q) use ($order) {
                            return $q->where("uniacid", $order->uniacid)->where("storeId", $order->storeId);
                        });
                    })->where("day", $order->completionDay)->update($order->statisticsData);
                }
                $order->save();
                $order->setLog($log);
                $order->orderIndex->isShow = $order->diningType  == 4 ? 0 : 1;
                $order->orderIndex->state = 8;
                $order->orderIndex->save();
            });
            if ($inStoreOrder->diningType == 4) {
                $inStoreOrder->state = $inStoreOrder->state == 8  ? 8 : 0;
                $inStoreOrder->orderIndex->state = 8;
                $inStoreOrder->orderIndex->isTj = 0;
                $inStoreOrder->save();
                $inStoreOrder->orderIndex->save();
            }
            DB::commit();
            //dispatch(new OrderStatisticsJob($inStoreOrder->orderSn));
            StaticService::tongji($inStoreOrder->orderSn);
            // if ($inStoreOrder->statisticsData) {
            //     StatisticsDay::where(function ($q) use ($inStoreOrder) {
            //         return $q->where(function ($q) use ($inStoreOrder) {
            //             return $q->where("uniacid", $inStoreOrder->uniacid)->where("storeId", 0);
            //         })->orWhere(function ($q) use ($inStoreOrder) {
            //             return $q->where("uniacid", $inStoreOrder->uniacid)->where("storeId", $inStoreOrder->storeId);
            //         });
            //     })->where("day", date("Y-m-d", time()))->update($inStoreOrder->statisticsData);
            // }
              InStoreOrderService::print($inStoreOrder->id, 6);
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage() . $e->getFile() . $e->getLine());
            throw new BadRequestException($e->getMessage());
        }
    }


    /**
     * 拒绝退款
     */
    public  static function rejectRefund($orderId, $refundMoney, $notes = '', $adminId = 0, $adminUser = '')
    {
        DB::beginTransaction();
        try {
            $order = Order::where("orderSn", $orderId)->where('state', 7)->first();
            if (empty($order)) {
                throw new BadRequestException('数据不存在');
            }
            if (!StoreAccountService::refundDown($order->storeId, $refundMoney, $adminId, $order->takeOutNo)) {
                DB::rollBack();
                throw  new BadRequestException('失败');
            };
            $order->state = 9;
            $order->setLog('门店拒绝退款，原因：' . $notes);
            $order->shopRefundNotes = $notes;
            $order->refundState = 2;
            $order->state = $order->beforRefundState;
            $order->beforRefundState = 0;
            $order->save();
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }


    /**
     * 申请退款
     */
    public static  function refundApply($orderId, $notes = '', $adminId = 0, $adminUser = '')
    {
        DB::beginTransaction();
        try {
            $order = Order::where('orderSn', $orderId)
                ->whereIn('state', [3, 4, 5, 6])
                ->whereIn('diningType', [5, 6])
                ->where('isPay', 1)
                ->first();
            if (empty($order)) {
                throw new BadRequestException('数据不存在');
            }
            if (!StoreAccountService::refundApply($order->storeId, $order->money, $order->userId, $order->takeOutNo)) {
                DB::rollBack();
                throw  new BadRequestException('申请退款失败');
            }
            $order->refundNotes = "用户申请";
            $order->afterSaleTime =   date("Y-m-d H:i:s", time());
            $order->userRefundNotes = $notes;
            $order->beforRefundState = $order->state;
            $order->state = 7;
            $order->refundState = 0;
            $order->setLog('用户申请退款，退款金额:' . $order->money . '，原因:' . $notes);
            $order->save();
            if ($order->save()) {
                DB::commit();
                Event(new StoreMessageEvent($order, 'refundApply'));
                return true;
            };
            DB::rollBack();
            return false;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 接单
     */
    public static function  received($orderId, $log = '')
    {
        try {
            Log::info("-----接单-------");
            $order = Order::where('id', $orderId)->first();
            if (empty($order)) {
                throw new BadRequestException('订单已取消或者已支付');
            }
            if (empty($order->prentOrderSn) && $order->diningType == 4) {
                $order = collect($order->subOrder)->whereIn('state', [1, 2, 3])->first();
            }
            $order->receiveTime = date("Y-m-d H:i:s", time());
            $order->state =  3;
            $order->setLog($log);
            $order->save();
            if ($order->diningType == 4) {
                Log::info("-----接单porder-------");
                Log::info($order->perentOrder);
                if ($order->perentOrder) {
                    $order->perentOrder->state = 3;
                    if (empty($order->perentOrder->openTime)) {
                        $order->perentOrder->openTime =  date("Y-m-d H:i:s", time());
                    }
                    $order->perentOrder->save();
                }
                Table::where('id', $order->tableId)
                    ->whereIn('state', [0, 1])
                    ->update([
                        'orderSn' => $order->prentOrderSn,
                        'state' => $order->payType == 1 ? 3 : 2,
                        'expiredTime' => $order->payType == 1 ? date("Y-m-d H:i:s", intval($order->store->inStoreSetting['order']['cleanTime'] * 60) + time()) : null,
                        'scan' => $order->source == 1 ? 1 : 0,
                        'openTime' => date("Y-m-d H:i:s", time())
                    ]);
                $lockKey = 'InstoreCheckout:' . $order->uniacid . $order->storeId . $order->tableId . $order->diningType;
                optional(Cache::lock($lockKey))->forceRelease();
            } else {
                if (in_array($order->diningType, [5, 6])) {
                    if (($order->diningType == 6 && in_array(2, $order->store->takeScreenSetting['score'] ?? [])
                            || ($order->diningType == 5 && in_array(3, $order->store->takeScreenSetting['score'] ?? [])))
                        && in_array($order->source, $order->store->takeScreenSetting['fastChannel'] ?? [1])
                    ) {
                        TakeScreen::updateOrcreate(['orderSn' => $order->orderSn], [
                            'orderSn' => $order->orderSn,
                            'state' => $order->state,
                            'pickNo' => $order->pickNo,
                            'storeId' => $order->storeId,
                            'uniacid' => $order->uniacid,
                            'packaging' => $order->packaging,
                            'orderTime' => $order->created_at,
                            'diningType' => $order->diningType,
                            'source' => $order->source
                        ]);
                    } elseif (in_array($order->source, [10, 11])) {
                        InStoreOrderService::complete($orderId);
                    }
                }
            }
            if ($order->addNum > 1) {
                InStoreOrderService::print($order->id, 2);
            } else {
                InStoreOrderService::print($order->id, 1);
            }
            if ($order->diningType == 4) {
                Event(new StoreMessageEvent($order->orderIndex, 'inStoreNewOrder'));
            } else {
                Event(new StoreMessageEvent($order->orderIndex, 'newOrder'));
            }
            return true;
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }





    /**
     * 订单完成
     */

    public static function complete($orderId, $log = '', $print = true)
    {
        DB::beginTransaction();
        try {
            $printSwitch = Request()->printSwitch == 2 ? 0 : 1;
            $orderIndex = Order::where('id', $orderId)->orWhere('orderSn', $orderId)->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('订单状态不正确');
            }
            $orderIndex->orderIndex->expiredTime = date("Y-m-d H:i:s", time() + 3600 * 24);
            $userId=$orderIndex->orderIndex->userId?:$orderIndex->userId;
            if (!$orderIndex->subOrder->isEmpty()) {
                $subOrder =  $orderIndex->subOrder;
                $orderIndex->state = 6;
                $orderIndex->orderIndex->state = 6;
                $orderIndex->completionTime = date("Y-m-d H:i:s", time());
                $orderIndex->expiredTime = date("Y-m-d H:i:s", time() + 3600 * 24);
                // $orderIndex->addUserPayStore();
                $orderIndex->setLog('用户已取单，订单已完成');
                $orderIndex->save();
                $orderIndex->orderIndex->save();
            } else {
                $subOrder[] = $orderIndex;
            }
            if($orderIndex->tableId){
                DB::table("table")->where('id', $orderIndex->tableId)->update([
                    'state' => 0,
                    'people' => 0,
                    'orderSn' => null,
                    'expiredTime' => null,
                    'scan' => 0,
                    "openTime" => null
                ]);
            }

            $memberInfo=$userId?Member::where('id',$userId)->first():'';
            //file_put_contents('complete.log',json_encode($orderIndex));
            //分銷訂單創建
            if ($orderIndex->userId) {
                $userInfo=Member::where('uniacid', $orderIndex->uniacid)->find($orderIndex->userId);
                PartnerOrder::createPartnerOrder($orderIndex->uniacid,$orderIndex->storeId,$orderIndex->userId,$userInfo->partnerId,$orderIndex->money,$orderIndex->orderSn);
            }
            collect($subOrder)->each(function ($order)use ($memberInfo,$userId)  {
                if($memberInfo){
                    if ($order->integral > 0 && $userId > 0) {
                        MemberAccountService::changeIntegral($userId, 1, $order->integral, MemberAccountLog::INTEGRAL_ORDER_GIVE, 0, "订单{$order->orderSn}赠送", $order->orderSn);
                    }
                    if ($order->exp > 0 && $userId > 0) {
                        MemberAccountService::changeExp($userId, 1, $order->exp, MemberAccountLog::EXP_ORDER_GIVE, 0, "订单{$order->orderSn}赠送", $order->orderSn);
                    }
                }

                $order->state = 6;
                $order->completionTime = date("Y-m-d H:i:s", time());
                $order->orderIndex->state = 6;
                $order->addUserPayStore();
                $order->setLog('用户已取单，订单已完成');
                $order->save();
                $order->orderIndex->save();
                if ($userId > 0 && $order->user) {
                    $order->user->isPay++;
                    $order->user->payTime = date("y-m-d H:i:s", time());
                    $order->user->save();
                }
                foreach ($order->goods as $key => $goods) {
                    GoodsSpu::where('id', $goods->spuId)->increment('sales', $goods->num);
                    Cache::increment("storeGoods:{$goods->storeId}:{$goods->spuId}", $goods->num);
                }
                TakeScreen::where("orderSn", $order->orderSn)->update(['state' => $order->state]);
                Event(new OrderMessageEvent($order, 'complete'));
                /**
                 * 支付有礼
                 */
                if ($order->payGiftId > 0 && $userId > 0) {
                    Event(new PayGiftEvent($order, 'pay'));
                }
                /**
                 * 集点有礼
                 */
                if ($order->collectId > 0 && $userId > 0) {
                    Event(new OrderCollectEvent($order, 'pay'));
                }

                if ($userId > 0 && $order->user->payOrder == 0) {
                    $partyB = PartyB::where('uniacid', $order->uniacid)
                        ->where('userId', $order->userId)
                        ->where('firstPayState', 0)
                        ->first();
                    if ($partyB) {
                        $partyA = PartyA::where('uniacid', $order->uniacid)
                            ->where('userId', $partyB->partyAid)
                            ->where('oldWithNewId', $partyB->oldWithNewId)
                            ->first();
                        if ($partyA) {
                            Event(new PartyBEvent($partyB, $partyA, 'firstPay'));
                        }
                    }
                }

                TakeScreen::where("orderSn", $order->orderSn)->update(['state' => $order->state]);
                if ($order->statisticsData) {
                    StatisticsDay::where(function ($q) use ($order) {
                        return $q->where(function ($q) use ($order) {
                            return $q->where("uniacid", $order->uniacid)->where("storeId", 0);
                        })->orWhere(function ($q) use ($order) {
                            return $q->where("uniacid", $order->uniacid)->where("storeId", $order->storeId);
                        });
                    })->where("day", date("Y-m-d", time()))
                        ->where("payMember", ">=", 0)
                        ->where("repurchase", ">=", 0)
                        ->where("newPayUser", ">=", 0)
                        ->where("newPayUser", ">=", 0)
                        ->update($order->statisticsData);
                }
            });
            $orderIndex->refresh();
            // if ($orderIndex->statisticsData) {
            //     StatisticsDay::where(function ($q) use ($orderIndex) {
            //         return $q->where(function ($q) use ($orderIndex) {
            //             return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", 0);
            //         })->orWhere(function ($q) use ($orderIndex) {
            //             return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", $orderIndex->storeId);
            //         });
            //     })->where("day", date("Y-m-d", time()))->update($orderIndex->statisticsData);
            // }
            DB::commit();
            //dispatch(new OrderStatisticsJob($orderIndex->orderSn));
            StaticService::tongji($orderIndex->orderSn);
            if ($orderIndex->diningType == 4) {
                Event(new StoreMessageEvent($orderIndex->orderIndex, 'complete'));
                if ($printSwitch) {
                    InStoreOrderService::print($orderIndex->id, 5);
                }
            }
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            file_put_contents('inStoreOrderService.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            throw new BadRequestException($e->getMessage());
        }
    }



    /**
     * 订单打印
     * "qtWmBusiness": 1, 商家联
     *   "qtWmCustomer": 1, 顾客联
     *   "qtWmRefund": 1,  退款联
     *  hcWmPrintNum： 厨房联
     * hcWmPrintMet  全部  1全部  3 分类 4 商品
     * hcWmGoodsClass 商品分类
     * hcWmSelectGoods  商品
     * hcWmPrintWay 打印方式  1 整单  2 一菜一单
     */
    //快餐     制作分单   制作整单    结账单
    //堂食     客单  制作分单  制作整单
    //加菜     客单   加菜制作分单
    //退菜     客单   退菜制作分单
    //             预结单
    //            结账单
    public static function print($orderId, $orderType = 1, $goods = [], $tableId = '', $toTableId = '')
    {
        try {
            switch ($orderType) {
                case 8;
                    $orderIndex = PersionPayOrder::where('id', $orderId)->orWhere('orderSn', $orderId)->first();
                    break;
                case 9;
                    $orderIndex = StoredValueOrder::where('id', $orderId)->orWhere('orderSn', $orderId)->first();
                    break;
                case 11;
                    $orderIndex = Handover::where('id', $orderId)->where('state', 1)->first();
                    break;
                case 12;
                    $orderIndex = QueuingUp::where('id', $orderId)->where('state', 1)->first();
                    break;
                default;
                    $orderIndex = Order::with('discounts')->where('id', $orderId)->orWhere('orderSn', $orderId)->first();
                    //$orderIndex->goods=!$orderIndex->goods->isEmpty()?$orderIndex->goods:$orderIndex->subGoods;
                    break;
            }
            $origin = $orderIndex->orderIndex->type ?: 4;
            if (empty($orderIndex) && $orderType <> 10) {
                throw new BadRequestException('订单不存在');
            }
            $payType = $orderIndex->payType;
            if ($orderType < 8 || $orderType == 13 || $orderType == 14) {
                if (empty($goods)) {
                    $goods = !$orderIndex->goods->isEmpty() ? $orderIndex->goods : $orderIndex->subGoods;
                }
            }
//            生成奶茶机二维码字符串
            //$naistr = SuodiQrcode::generateNaistr($goods);
            //var_dump($naistr);
           // $orderIndex->naicode = $naistr;
            $printer = Printer::getHardware($orderIndex->uniacid, $orderIndex->storeId, '', 2);

            if (empty($printer)) {
                return true;
            }
            $area=Table::where('id',$orderIndex->tableId)->first();
            foreach ($printer as $k => $v) {
                try {
                    if($orderIndex->tableId){
                        if($v['rule']['config']['areaData']){
                            if(!in_array($area->areaId,$v['rule']['config']['areaData'])){
                                continue;
                            }
                        }
                    }
                    switch ($v['type']) {
                        case 2:
                            if ($orderType == 1 || $orderType == 2 || $orderType == 14) {
                                if ($v['vendor'] == 'feie') {
                                    $printer_type = 7;
                                    $contents = FeieLabelContent::labelAllContent($orderIndex, $goods, $v['rule']);
                                    foreach ($contents as $key => $content) {
                                        $content .= "<DIRECTION>1</DIRECTION>";
                                        $data = Printer::feiPrint($v, $content, 3);
                                        $respond = json_decode($data, true);
                                        if ($respond['msg'] == 'ok' && $respond['ret'] == 0) {
                                            $respond['msg'] = '成功';
                                        }
                                        PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '厨房联一菜一单');
                                    }
                                }
                                if ($v['vendor'] == 'xinye') {
                                    $printer_type = 8;
                                    $contents = XinyeLabelContent::labelAllContent($orderIndex, $goods, $v['rule']);
                                    foreach ($contents as $key => $content) {
                                        $data = Printer::xinyePrint($v, $content, 3);
                                        $respond = json_decode($data, true);
                                        if ($respond['msg'] == 'ok' && $respond['code'] == 0) {
                                            $respond['msg'] = '成功';
                                        }
                                        PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '厨房联一菜一单');
                                    }
                                }
                            }
                            break;
                        case 1:
                            switch ($v['vendor']) {
                                case 'feie';
                                    $printer_type = 1;
                                    if ($v['config']['printer_size'] == 2) {
                                        $className = "App\Services\Print\FeieMaxContent";
                                    } else {
                                        $className = "App\Services\Print\FeieContent";
                                    }
                                    $action = 'feiPrint';
                                    break;
                                case 'esLink';
                                    $printer_type = 2;
                                    if ($v['config']['printer_size'] == 2) {
                                        $className = "App\Services\Print\YlyMaxContent";
                                    } else {
                                        $className = "App\Services\Print\YlyContent";
                                    }
                                    $action = 'ylyPrint';
                                    break;
                                case 'spyun';
                                    $printer_type = 3;
                                    $className = "App\Services\Print\SpyunContent";
                                    $action = 'spyPrint';
                                    break;
                                case 'daqu';
                                    $printer_type = 4;
                                    $className = "App\Services\Print\DaquContent";
                                    $action = 'daquPrint';
                                    break;
                                case 'jiabo';
                                    $printer_type = 5;
                                    $className = "App\Services\Print\JiaboContent";
                                    $action = 'jiaboPrint';
                                    break;
                                case 'xinye';
                                    $printer_type = 6;
                                    if ($v['config']['printer_size'] == 2) {
                                        $className = "App\Services\Print\XinyeMaxContent";
                                    } else {
                                        $className = "App\Services\Print\XinyeContent";
                                    }
                                    $action = 'xinyePrint';
                                    break;
                                default;
                                    $printer_type = 1;
                                    $className = "App\Services\Print\FeieContent";
                                    $action = 'feiPrint';
                                    break;
                            }

                            switch ($orderType) {
                                case 1; //客单  制作分单  制作整单
                                    if (isset($v['rule']['config']['kdPrintNum']) && $v['rule']['config']['kdPrintNum'] > 0) {
                                        $content = '';
                                        $printGoods = $goods;
                                        // if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                        //     $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                        //         if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                        //             return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                        //         }
                                        //     })->all();
                                        // } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                        //     $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                        //         if ($value->spuId && $v['rule']['config']['hcWmSelectGoods']) {
                                        //             return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                        //         }
                                        //     })->all();
                                        // } else {
                                        //     $printGoods = $goods;
                                        // }
                                        $content .= $className::instoreCustomerContents($orderIndex, 1, $printGoods);
                                        $num = $v['rule']['config']['kdPrintNum'] ?? 1;
                                        if ($content) {
                                            if (strlen($content) > 5000) {
                                                $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                            } else {
                                                $data = Printer::$action($v, $content, 2, $num);
                                            }
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '客单');
                                        }
                                    }
                                    if (isset($v['rule']['config']['zdPrintNum']) && $v['rule']['config']['zdPrintNum'] > 0) {
                                        $content = '';
                                        $printGoods = [];
                                        if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                                    return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                                }
                                            })->all();
                                        } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if ($value->spuId && $v['rule']['config']['hcWmSelectGoods']) {
                                                    return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                                }
                                            })->all();
                                        } else {
                                            $printGoods = $goods;
                                        }
                                        if ($printGoods) {
                                            $content .= $className::instoreCustomerContents($orderIndex, 6, $printGoods);
                                            $num = $v['rule']['config']['zdPrintNum'] ?? 1;
                                            if ($content) {
                                                if (strlen($content) > 5000) {
                                                    $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                    $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                                } else {
                                                    $data = Printer::$action($v, $content, 2, $num);
                                                }
                                                PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '厨房联制作总单');
                                            }
                                        }
                                    }
                                    if (isset($v['rule']['config']['hcWmPrintNum']) && $v['rule']['config']['hcWmPrintNum'] > 0) {
                                        $printGoods = [];
                                        if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                                    return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                                }
                                            })->all();
                                        } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if ($value->spuId && $v['rule']['config']['hcWmSelectGoods']) {
                                                    return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                                }
                                            })->all();
                                        } else {
                                            $printGoods = $goods;
                                        }
                                        $num = $v['rule']['config']['hcWmPrintNum'] ?? 1;
                                        if ($printGoods) {
                                            foreach ($printGoods as $vo) {
                                                $content = '';
                                                $content .= $className::instoreCustomerContents($orderIndex, 7, [$vo]);
                                                $data = Printer::$action($v, $content, 2, $num);
                                                PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '厨房联一菜一单');
                                            }
                                        }
                                    }
                                    if ($orderIndex->diningType == 6 && isset($v['rule']['config']['jzdPrintNum']) && $v['rule']['config']['jzdPrintNum'] > 0) {
                                        $content = '';
                                        $printGoods = [];
                                        $printGoods = $goods;
                                        foreach ($printGoods as $ks => $vs) {
                                            if ($vs->discountLabel == '退') {
                                                unset($printGoods[$ks]);
                                            }
                                        }
                                        $content .= $className::instoreCustomerContents($orderIndex, 3, $goods);
                                        $num = $v['rule']['config']['jzdPrintNum'] ?? 1;
                                        if ($content) {
                                            if (strlen($content) > 5000) {
                                                $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                            } else {
                                                $data = Printer::$action($v, $content, 2, $num);
                                            }
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '结账单');
                                        }
                                    }
                                    break;
                                case 2; //客单   加菜制作分单
                                    if (isset($v['rule']['config']['kdPrintNum']) && $v['rule']['config']['kdPrintNum'] > 0) {
                                        $content = '';
                                        $printGoods = $goods;
                                        // if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                        //     $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                        //         if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                        //             return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                        //         }
                                        //     })->all();
                                        // } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                        //     $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                        //         if ($value->spuId && $v['rule']['config']['hcWmSelectGoods']) {
                                        //             return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                        //         }
                                        //     })->all();
                                        // } else {
                                        //     $printGoods = $goods;
                                        // }
                                        if ($printGoods) {
                                            $content .= $className::instoreCustomerContents($orderIndex, 8, $printGoods);
                                            $num = $v['rule']['config']['kdPrintNum'] ?? 1;
                                            if ($content) {
                                                if (strlen($content) > 5000) {
                                                    $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                    $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                                } else {
                                                    $data = Printer::$action($v, $content, 2, $num);
                                                }
                                                PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '客单(加菜)');
                                            }
                                        }
                                    }

                                    if (isset($v['rule']['config']['zdPrintNum']) && $v['rule']['config']['zdPrintNum'] > 0) {
                                        $printGoods = [];
                                        if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                                    return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                                }
                                            })->all();
                                        } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if ($value->spuId && $v['rule']['config']['hcWmSelectGoods']) {
                                                    return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                                }
                                            })->all();
                                        } else {
                                            $printGoods = $goods;
                                        }
                                        if ($printGoods) {
                                            $content = '';
                                            $content .= $className::instoreCustomerContents($orderIndex, 6, $printGoods);
                                            $num = $v['rule']['config']['zdPrintNum'] ?? 1;
                                            if ($content) {
                                                if (strlen($content) > 5000) {
                                                    $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                    $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                                } else {
                                                    $data = Printer::$action($v, $content, 2, $num);
                                                }
                                                PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '厨房联制作总单(加菜)');
                                            }
                                        }
                                    }
                                    if (isset($v['rule']['config']['hcWmPrintNum']) && $v['rule']['config']['hcWmPrintNum'] > 0) {
                                        $printGoods = [];
                                        if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                                    return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                                }
                                            })->all();
                                        } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if ($value->spuId && $v['rule']['config']['hcWmSelectGoods']) {
                                                    return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                                }
                                            })->all();
                                        } else {
                                            $printGoods = $goods;
                                        }
                                        if ($printGoods) {
                                            $num = $v['rule']['config']['hcWmPrintNum'] ?? 1;
                                            foreach ($printGoods as $vo) {
                                                $content = '';
                                                $content .= $className::instoreCustomerContents($orderIndex, 9, [$vo]);
                                                if ($content) {
                                                    $data = Printer::$action($v, $content, 2, $num);
                                                    PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '厨房联一菜一单(加菜)');
                                                }
                                            }
                                        }
                                    }
                                    break;
                                case 3; //客单   退菜制作分单
                                    if (isset($v['rule']['config']['kdPrintNum']) && $v['rule']['config']['kdPrintNum'] > 0) {
                                        $content = '';
                                        $printGoods = $goods;
                                        // if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                        //     $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                        //         if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                        //             return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                        //         }
                                        //     })->all();
                                        // } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                        //     $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                        //         if ($value->spuId && $v['rule']['config']['hcWmSelectGoods']) {
                                        //             return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                        //         }
                                        //     })->all();
                                        // } else {
                                        //     $printGoods = $goods;
                                        // }
                                        if ($printGoods) {
                                            $content .= $className::instoreCustomerContents($orderIndex, 1, $printGoods);
                                            $num = $v['rule']['config']['kdPrintNum'] ?? 1;
                                            if ($content) {
                                                if (strlen($content) > 5000) {
                                                    $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                    $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                                } else {
                                                    $data = Printer::$action($v, $content, 2, $num);
                                                }
                                                PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '客单(退菜)');
                                            }
                                        }
                                    }
                                    if (isset($v['rule']['config']['hcWmPrintNum']) && $v['rule']['config']['hcWmPrintNum'] > 0) {
                                        $printGoods = [];
                                        if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                                    return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                                }
                                            })->all();
                                        } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if ($value->spuId && $v['rule']['config']['hcWmSelectGoods']) {
                                                    return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                                }
                                            })->all();
                                        } else {
                                            $printGoods = $goods;
                                        }
                                        if ($printGoods) {
                                            $num = $v['rule']['config']['hcWmPrintNum'] ?? 1;
                                            foreach ($printGoods as $vo) {
                                                $content = '';
                                                $content .= $className::instoreCustomerContents($orderIndex, 7, [$vo]);
                                                if ($content) {
                                                    $data = Printer::$action($v, $content, 2, $num);
                                                    PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '厨房联一菜一单(退菜)');
                                                }
                                            }
                                        }
                                    }
                                    break;
                                case 4; //预结单
                                    if (isset($v['rule']['config']['yjdPrintNum']) && $v['rule']['config']['yjdPrintNum'] > 0) {
                                        //                                    foreach ($goods as $ks => $vs) {
                                        //                                        if ($vs->discountLabel == '退') {
                                        //                                            unset($goods[$ks]);
                                        //                                        }
                                        //                                    }
                                        $content = '';
                                        $content .= $className::instoreCustomerContents($orderIndex, 2, $goods);
                                        $num = $v['rule']['config']['yjdPrintNum'] ?? 1;
                                        if ($content) {
                                            if (strlen($content) > 5000) {
                                                $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                            } else {
                                                $data = Printer::$action($v, $content, 2, $num);
                                            }
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '预结单');
                                        }
                                    }
                                    break;
                                case 5; //结账单
                                    if (isset($v['rule']['config']['jzdPrintNum']) && $v['rule']['config']['jzdPrintNum'] > 0) {
                                        $content = '';
                                        $content .= $className::instoreCustomerContents($orderIndex, 3, $goods);
                                        $num = $v['rule']['config']['jzdPrintNum'] ?? 1;
                                        if ($content) {
                                            if (strlen($content) > 5000) {
                                                $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                            } else {
                                                $data = Printer::$action($v, $content, 2, $num);
                                            }
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '结账单');
                                        }
                                    }
                                    break;
                                case 6; //退菜单
                                    if (isset($v['rule']['config']['qtWmRefund']) && $v['rule']['config']['qtWmRefund'] > 0) {
                                        $printGoods = [];
                                        if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                                    return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                                }
                                            })->all();
                                        } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if ($value->spuId && is_array($v['rule']['config']['hcWmSelectGoods'])) {
                                                    return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                                }
                                            })->all();
                                        } else {
                                            $printGoods = $goods;
                                        }
                                        //file_put_contents('退菜.log', json_encode($goods) . PHP_EOL, FILE_APPEND);
                                        $content = '';
                                        $reason = '';
                                        foreach ($printGoods as $vo) {
                                            $reason = $vo->reason;
                                            $content .= $className::instoreCustomerContents($orderIndex, 10, [$vo], $reason);
                                        }
                                        $num = $v['rule']['config']['qtWmRefund'] ?? 1;
                                        if ($content) {
                                            $data = Printer::$action($v, $content, 2, $num);
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '退菜单');
                                        }
                                    }
                                    break;
                                case 7; //补打客单
                                    if (isset($v['rule']['config']['kdPrintNum']) && $v['rule']['config']['kdPrintNum'] > 0) {
                                        foreach ($goods as $ks => $vs) {
                                            if ($vs->discountLabel == '退') {
                                                unset($goods[$ks]);
                                            }
                                        }
                                        $content = '';
                                        $content .= $className::instoreCustomerContents($orderIndex, 1, $goods);
                                        $num = $v['rule']['config']['kdPrintNum'] ?? 1;
                                        if ($content) {
                                            $data = Printer::$action($v, $content, 2, $num);
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '补打客单');
                                        }
                                    }
                                    break;
                                case 8; //当面付订单
                                    $content = '';
                                    $num = $v['rule']['config']['dmfPrintNum'];
                                    if ($num) {
                                        $content .= $className::facepayContents($orderIndex);
                                        if ($content) {
                                            $data = Printer::$action($v, $content, 2, $num);
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, 8, '当面付订单');
                                        }
                                    }
                                    break;
                                case 9; //充值订单
                                    $content = '';
                                    $num = $v['rule']['config']['czPrintNum'];
                                    if ($num) {
                                        $userId = $orderIndex->userId;
                                        $member = MemberAccount::where('userId', $userId)->first();
                                        $content .= $className::rechargeContents($orderIndex, $member->balance);
                                        if ($content) {
                                            $data = Printer::$action($v, $content, 2, $num);
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, 9, '充值订单');
                                        }
                                    }
                                    break;
                                case 10; //餐桌换台
                                    $content = '';
                                    $num = $v['rule']['config']['turntable'];
                                    if ($num) {
                                        $content .= $className::turntable($tableId, $toTableId, $orderIndex, '');
                                        if ($content) {
                                            $data = Printer::$action($v, $content, 2, $num);
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, 10, '餐桌换台');
                                        }
                                    }
                                    break;
                                case 11; //收银台交班
                                    $content = '';
                                    $num = $v['rule']['config']['handoverNum'];
                                    if ($num) {
                                        $content .= $className::handoverNote($orderIndex);
                                        if ($content) {
                                            $data = Printer::$action($v, $content, 2, $num);
                                            PrinterLog::registerLog($v, $orderIndex->id, $printer_type, $content, $data, 11, '收银台交班');
                                        }
                                    }
                                    break;
                                case 12; //排队取号
                                    $content = '';
                                    $num = $v['rule']['config']['queuingNum'];
                                    if ($num) {
                                        $content .= $className::queuingNumber($orderIndex);
                                        if ($content) {
                                            $data = Printer::$action($v, $content, 2, $num);
                                            PrinterLog::registerLog($v, $orderIndex->id, $printer_type, $content, $data, 12, '排队取号');
                                        }
                                    }
                                    break;
                                case 13; //补打制作总单
                                    $content = '';
                                    if (isset($v['rule']['config']['zdPrintNum']) && $v['rule']['config']['zdPrintNum'] > 0) {
                                        $content = '';
                                        $printGoods = [];
                                        if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                                    return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                                }
                                            })->all();
                                        } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if ($value->spuId && $v['rule']['config']['hcWmSelectGoods']) {
                                                    return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                                }
                                            })->all();
                                        } else {
                                            $printGoods = $goods;
                                        }
                                        if ($printGoods) {
                                            $content .= $className::instoreCustomerContents($orderIndex, 6, $printGoods);
                                            $num = $v['rule']['config']['zdPrintNum'] ?? 1;
                                            if ($content) {
                                                if (strlen($content) > 5000) {
                                                    $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                    $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                                } else {
                                                    $data = Printer::$action($v, $content, 2, $num);
                                                }
                                                PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '补打厨房联制作总单');
                                            }
                                        }
                                    }
                                    break;
                                case 14; //补打制作分单
                                    $content = '';
                                    if (isset($v['rule']['config']['hcWmPrintNum']) && $v['rule']['config']['hcWmPrintNum'] > 0) {
                                        $printGoods = [];
                                        if ($v['rule']['config']['hcWmPrintMet'] == 3) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if (is_array($value->spu->catId) && is_array($v['rule']['config']['hcWmGoodsClass'])) {
                                                    return !empty(array_intersect($value->spu->catId, $v['rule']['config']['hcWmGoodsClass']));
                                                }
                                            })->all();
                                        } elseif ($v['rule']['config']['hcWmPrintMet'] == 4) {
                                            $printGoods = collect($goods)->filter(function ($value, $key) use ($v) {
                                                if ($value->spuId && $v['rule']['config']['hcWmSelectGoods']) {
                                                    return in_array($value->spuId, $v['rule']['config']['hcWmSelectGoods']);
                                                }
                                            })->all();
                                        } else {
                                            $printGoods = $goods;
                                        }
                                        $num = $v['rule']['config']['hcWmPrintNum'] ?? 1;
                                        if ($printGoods) {
                                            foreach ($printGoods as $vo) {
                                                $content = '';
                                                $content .= $className::instoreCustomerContents($orderIndex, 7, [$vo]);
                                                $data = Printer::$action($v, $content, 2, $num);
                                                PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '补打厨房联一菜一单');
                                            }
                                        }
                                    }
                                    break;
                            }
                            break;
                        default:
                            break;
                    }
                } catch (\Exception $e) {
                    file_put_contents('printError.log', $e->getMessage());
                    Log::error($e->getMessage());
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return true;
        }
    }



    /**
     * 制作完成
     */
    public static function maked($orderId, $log = null)
    {
        try {
            $order = Order::where('state', 3)->whereIn('diningType', [5, 6])->where('id', $orderId)->first();
            if (empty($order)) {
                throw new BadRequestException('数据不存在');
            }
            $order->makedTime = date("Y-m-d H:i:s", time());
            $order->state = 4;
            $order->save();
            $order->setLog("商品制作完成，等待用户取单");
            TakeScreen::updateOrcreate(['orderSn' => $order->orderSn], [
                'orderSn' => $order->orderSn,
                'state' => $order->state,
                'pickNo' => $order->pickNo,
                'storeId' => $order->storeId,
                'uniacid' => $order->uniacid,
                'packaging' => $order->packaging,
                'orderTime' => $order->created_at,
                'diningType' => $order->diningType,
                'source' => $order->source
            ]);
            Event(new OrderMessageEvent($order, 'takeMeal'));
            // Event(new StoreMessageEvent($order->orderIndex, 'call'));
            return true;
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }

    public static function callNum($orderId, $log = null)
    {
        $orderIndex = Order::where('id', $orderId)->first();
        if (empty($orderIndex)) {
            throw new BadRequestException('订单状态不正确');
        }
        Event(new StoreMessageEvent($orderIndex->orderIndex, 'call'));
    }

    public static function backTable($orderId, $log = null, $adminId = 0)
    {
        try {
            DB::beginTransaction();
            $order = Order::where('orderSn', $orderId)
                ->whereIn('state', [1, 2, 3])
                ->where('diningType', 4)
                ->where('payType', 2)
                ->whereNull('prentOrderSn')
                ->first();
            if (empty($order)) {
                throw new BadRequestException('数据不存在');
            }
            $order->adminId = $adminId;
            $order->state = 0;
            $order->refundNotes = $log;
            $order->save();
            $order->setLog($log);
            $order->orderIndex->isShow = 0;
            $order->orderIndex->state = 0;
            $order->orderIndex->save();
            $order->subOrder()->update([
                'state' => 0,
            ]);
            $couponIds = collect($order->subOrder)->pluck('couponId')->all();
            MemberCoupon::whereIn('id', $couponIds)->update([
                'state' => 1,
                'orderId' => 0,
                'updated_at' => null
            ]);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }



    public static function createOrder($request, $storeId, $userId, $tableId, $appType, $uniacid)
    {
        $checkoutKey = 'InstoreCheckout:Store:' . $storeId . $userId . $tableId . ($request->diningType ?? 0) . $appType;
        $checkout = Cache::get($checkoutKey);
        if (empty($checkout)) {
            throw new BadRequestException('请先到收银台结账');
        }
        if ($checkout->diningType == 4) {
            $lockKey = 'InstoreCheckout:' . $checkout->uniacid . $checkout->storeId . $checkout->tableId . $checkout->diningType;
        } else {
            $lockKey = 'InstoreCheckout:' . $checkout->uniacid . $checkout->storeId . $checkout->tableId . $checkout->diningType . $checkout->userId . $userId;
        }
        $isLocked = Cache::lock($lockKey, 10);
        if (!$isLocked->get()) {
            throw new BadRequestException('订单提交中,请勿重复提交');
        }
        try {
            DB::beginTransaction();
            $checkout->check();
            $data = $checkout->toArray();
            unset($data['reservationTime'], $data['addressList']);
            $model = new Order();
            $model->fill($data);
            $model->source = $appType;
            $model->state = 1;
            $model->uniacid = $uniacid;
            $model->pickNo = '';
            $model->autoReceive = $checkout->autoReceive;
            $model->receivePrint = $checkout->receivePrint;
            $model->orderSn = getTakeOutNo();
            $model->serverTime = null;
            $model->prentOrderSn = $checkout->prentOrderSn;
            $model->goodsNum = $checkout->goodsNum;
            $notes=Cache::get("$checkoutKey".$checkout->tableId.'notes');
            Cache::delete("$checkoutKey".$checkout->tableId.'notes');
            $model->notes=$notes;
            if (empty($model->prentOrderSn) && $model->diningType == 4) {
                $table = DB::table('table')->find($model->tableId);
                if ($table->state != 1) {
                    throw new BadRequestException('桌位状态已改变，无法提交');
                }
                $pOrder = new Order();
                $pOrder->fill($model->toArray());
                $pOrder->orderSn = getTakeOutNo();
                $pOrder->source = $appType;
                $pickNo = $pOrder->getPickNo();
                $pOrder->pickFix = $pOrder->getPickFix();
                $pOrder->pickNo = $pickNo;
                $model->notes=$notes;
                $pOrder->save();
                $model->prentOrderSn = $pOrder->orderSn;
                $model->pickFix = $pOrder->pickFix;
                $model->pickNo = $pickNo;
                OrderIndex::where('orderSn', $model->orderSn)->update(['isSub' => 1]);
                Table::where('id', $model->tableId)
                    ->update([
                        'orderSn' => $model->prentOrderSn,
                        'state' => 2,
                        'scan' => 0,
                    ]);
            }

            $res = ConfigService::getChannelConfig('basicSetting', $uniacid);
            if($res['service_charge']){
                $percentage=$res['service_charge'];
                $percentage /= 100;
                $service_money = bcmul($model->money,$percentage,2);
                $model->service_charge=$res['service_charge'];
                $model->service_money=$service_money;
                $model->money=bcadd($data['money'],$service_money,2);
                DB::table("instore_order")->where('orderSn', $model->prentOrderSn)->update([
                    'service_charge' => $res['service_charge'],
                    'service_money' => $service_money,
                ]);
            }
            $model->save();
            if ($model->notes) {
                DB::table("instore_order")->where('orderSn', $model->prentOrderSn)->update([
                    'notes' => $model->notes
                ]);
            }
            foreach ($checkout->discounts as $key => $discount) {
                $discount['uniacid'] = $model->uniacid;
                $discount['orderId'] = $model->id;
                $discount['storeId'] = $model->storeId;
                $discount['userId'] = $model->userId;
                $discount['orderSn'] = $model->orderSn;
                $discount['prentOrderSn'] = $model->prentOrderSn;
                Discount::create($discount);
            }
            if ($model->couponId && $model->payType == 2) {
                MemberCoupon::where('id', $model->couponId)->update([
                    'state' => 2,
                    'updated_at' => date("Y-m-d H:i:s", time()),
                    'orderId' => $model->id
                ]);
            }
            foreach ($checkout->goodsList as $key => $goods) {
                $goodsItem = $goods->toArray();
                unset($goodsItem['id']);
                $goodsItem['name'] = $goods->goods->name;
                $goodsItem['logo'] = $goods->goods->logo;
                $goodsItem['orderSn'] = $model->orderSn;
                $goodsItem['prentOrderSn'] = $model->prentOrderSn;
                $orderGoods[] = new OrderGoods($goodsItem);
                $goods->delete();
            }
            $model->goods()->saveMany($orderGoods);
            if ($model->prentOrderSn) {
                $model->perentOrder->state = $model->state;
                $model->perentOrder->changeData();
            }
            $model->refresh();
            optional($isLocked)->release();
            DB::commit();
            return $model;
        } catch (\Exception $e) {
            DB::rollBack();
            optional($isLocked)->release();
            Log::error($e->getMessage() . $e->getFile() . $e->getLine());
            throw new BadRequestException($e->getMessage());
        }
    }
}
