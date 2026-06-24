<?php

namespace App\Services;

use App\Events\OrderCollectEvent;
use App\Events\OrderMessageEvent;
use App\Events\PartnerEvent;
use App\Events\PartyBEvent;
use App\Events\PayGiftEvent;
use App\Events\StoreMessageEvent;
use App\Jobs\OrderStatisticsJob;
use App\Models\BulkPackage;
use App\Models\Coupon\MemberCoupon;
use App\Models\GoodsSpu;
use App\Models\MemberAccountLog;
use App\Models\OldWithNew\PartyA;
use App\Models\OldWithNew\PartyB;
use App\Models\Order\OrderIndex;
use App\Models\Order\TakeOutOrder;
use App\Models\Order\TakeScreen;
use App\Models\OrderCollect\OrderCollect;
use App\Models\OrderLog;
use App\Models\PartnerOrder;
use App\Models\Printer;
use App\Models\PrinterLog;
use App\Models\RefundOrder;
use App\Models\StatisticsDay;
use App\Models\TakeOut\Delivery;
use App\Services\Print\DaquContent;
use App\Services\Print\FeieContent;
use App\Services\Print\FeieLabelContent;
use App\Services\Print\JiaboContent;
use App\Services\Print\XinyeLabelContent;
use App\Services\Print\YlyContent;
use Cache;
use Illuminate\Support\Facades\Cache as Caches;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Services\Print\SpyunContent;
use App\Jobs\Order\DeliveryCallJob;
use App\Models\Member;
class OrderService extends BaseService
{

    /**
     * 关闭订单
     */
    public  static function close($orderId, $log = '')
    {
        try {
            DB::beginTransaction();
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($orderId) {
                return $q->unPaid()->where('id', $orderId);
            })->unPaid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            $orderIndex->state = 0;
            $orderIndex->save();
            $orderIndex->order->state = 0;
            $orderIndex->order->setLog($log);
            $orderIndex->order->save();
            $orderIndex->order->goods()->delete();
            collect($orderIndex->order->goods)->map(function ($goods) {
                $goods->delete();
            });
            if ($orderIndex->order->couponId) {
                MemberCoupon::where('orderSn', $orderIndex->order->orderSn)->update([
                    'state' => 1,
                    'orderId' => 0,
                    'orderSn' => null,
                    'updated_at' => null
                ]);
            }
            $newSub = collect($orderIndex->order->discount)->filter(function ($discount, $key,) {
                return $discount->type == 'newSub';
            })->toArray();
            if ($newSub) {
                Cache::set('newSub:' . $orderIndex->userId, 0);
            }
            DB::commit();
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
    public  static function refund($orderId, $refundMoney = 0, $adminId = 0, $notes = '', $log = null)
    {
        DB::beginTransaction();
        try {
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($orderId) {
                return $q->whereIn('state', [2, 3, 4, 5, 6, 7])->where('id', $orderId);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            $refundMoney = $refundMoney>0?$refundMoney:$orderIndex->order->money;
            $order = [
                'takeOutNo' => $orderIndex->orderSn,
                'openid' => $orderIndex->payer,
                'transaction_id' => $orderIndex->thirdNo,
                'total_amount' => $orderIndex->order->money,
                'refund_amount' => $refundMoney,
            ];
            if (in_array($orderIndex->order->state, [1, 2, 3, 4, 5, 6])) {
                $orderIndex->order->refundNotes = "门店申请";
                if (empty($log)) {
                    $orderIndex->order->state = 7;
                    $orderIndex->order->refundCause = $notes;
                    $orderIndex->order->setLog('门店发起退款，原因:' . $notes);
                }
                if ($orderIndex->payChannel == 1) {
                    if (!StoreAccountService::refundDirectly($orderIndex->storeId, $refundMoney, $orderIndex->userId, $orderIndex->takeOutNo)) {
                        DB::rollBack();
                        throw  new BadRequestException('申请退款失败');
                    }
                }
            } else {
                if ($orderIndex->payChannel == 1) {
                    if (!StoreAccountService::refund($orderIndex->storeId, $refundMoney, $adminId, $orderIndex->takeOutNo)) {
                        DB::rollBack();
                        throw  new BadRequestException('申请退款失败');
                    }
                }
            }
            $refundOrder = RefundOrder::where('takeOutNo', $orderIndex->orderSn)->first();
            if (empty($refundOrder) || $orderIndex->payType < 100) {
                $res = PayService::refund($order, $orderIndex->uniacid, $orderIndex->payTempId);
            } else {
                $res = true;
            }
            if ($res) {
                $orderIndex->order->afterSaleCompletion =   date("y-m-d H:i:s", time());
                $orderIndex->order->refundMoney = bcadd($orderIndex->order->refundMoney, $refundMoney, 2);
                $orderIndex->user->payOrder--;
                if ($refundMoney ==  $orderIndex->order->money) {
                    $orderIndex->order->state = 8;
                    $orderIndex->state = 8;
                    $orderIndex->order->refundState = 1;
                }
                if ($orderIndex->order->getOriginal('state') == 7) {
                    $orderIndex->order->setLog(!empty($log) ? $log : "门店已同意退款，退款金额:{$refundMoney}，订单已关闭");
                } else {
                    $storelog = "退款金额:{$refundMoney}，订单已关闭" . $orderIndex->order->state == 8 ? "，订单已关闭" : '';
                    $orderIndex->order->setLog(!empty($log) ? $log : $storelog);
                }
                if ($orderIndex->order->couponId) {
                    MemberCoupon::where('id', $orderIndex->order->couponId)->update([
                        'state' => 1,
                        'orderId' => 0,
                        'updated_at' => null,
                    ]);
                }
                if ($orderIndex->order->getOriginal('state') == 6 && $orderIndex->order->state == 8) {
                    if ($orderIndex->order->integral > 0) {
                        MemberAccountService::changeIntegral($orderIndex->order->userId, 2, $orderIndex->order->integral, MemberAccountLog::INTEGRAL_ORDER_GIVE, 0, "订单{$orderIndex->orderSn}赠送", $orderIndex->orderSn);
                    }
                    if ($orderIndex->order->exp > 0) {
                        MemberAccountService::changeExp($orderIndex->order->userId, 2, $orderIndex->order->exp, MemberAccountLog::EXP_ORDER_GIVE, 0, "订单{$orderIndex->orderSn}赠送", $orderIndex->orderSn);
                    }

                    TakeScreen::where("orderSn", $orderIndex->orderSn)->delete();
                    $orderIndex->order->refundUserPayStore();
                    $orderIndex->user->isPay--;
                    /**
                     * 支付有利撤回
                     */
                    if ($orderIndex->order->payGiftId) {
                        Event(new PayGiftEvent($orderIndex->order, 'refund'));
                    }
                    /**
                     * 集点有利撤回
                     */
                    if ($orderIndex->order->collectId) {
                        Event(new OrderCollectEvent($orderIndex->order, 'refund'));
                    }
                    event(new PartnerEvent($orderIndex));
                } elseif (in_array($orderIndex->order->changeBeforState, [4, 5]) && $orderIndex->order->scene == 1 && $orderIndex->order->deliveryOrder) {
                    $orderIndex->order->deliveryOrder->close();
                    $orderIndex->order->deliveryOrder->orderRefund = 1;
                    $orderIndex->order->deliveryOrder->save();
                }
                collect($orderIndex->order->goods)->map(function ($goods) {
                    Cache::decrement("storeGoods:{$goods->storeId}:{$goods->spuId}", $goods->num);
                    $goods->delete();
                });
                $orderIndex->order->save();
                $orderIndex->user->save();
                $orderIndex->save();
                TakeScreen::where('orderSn', $orderIndex->order->orderSn)->delete();
                DB::commit();
                if ($orderIndex->order->statisticsData) {
                    StatisticsDay::where(function ($q) use ($orderIndex) {
                        return $q->where(function ($q) use ($orderIndex) {
                            return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", 0);
                        })->orWhere(function ($q) use ($orderIndex) {
                            return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", $orderIndex->storeId);
                        });
                    })->where("day", $orderIndex->order->completionDay)
                        ->where("payMember", ">=", 0)
                        ->where("repurchase", ">=", 0)
                        ->where("newPayUser", ">=", 0)
                        ->where("newPayUser", ">=", 0)
                        ->update($orderIndex->order->statisticsData);
                }
                Event(new OrderMessageEvent($orderIndex->order, 'refund'));
                //dispatch(new OrderStatisticsJob($orderIndex->orderSn));
                StaticService::tongji($orderIndex->orderSn);
                return true;
            }
            DB::rollBack();
            return false;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
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
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($orderId) {
                return $q->refundApply()->where("id", $orderId);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            if (!StoreAccountService::refundDown($orderIndex->storeId, $orderIndex->order->money, $adminId, $orderIndex->takeOutNo)) {
                DB::rollBack();
                throw  new BadRequestException('失败');
            };
            $orderIndex->order->state = 9;
            $orderIndex->order->setLog('门店拒绝退款，原因：' . $notes);
            $orderIndex->order->shopRefundNotes = $notes;
            $orderIndex->order->refundState = 2;
            $orderIndex->order->state = $orderIndex->order->beforRefundState;
            $orderIndex->order->beforRefundState = 0;
            $orderIndex->order->save();
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
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($orderId) {
                return $q->whereIn("state", [2, 3, 4, 5, 6])->where("id", $orderId);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            if ($orderIndex->payChannel == 1 && !StoreAccountService::refundApply($orderIndex->storeId, $orderIndex->order->money, $orderIndex->userId, $orderIndex->takeOutNo)) {
                DB::rollBack();
                throw  new BadRequestException('申请退款失败');
            }
            $orderIndex->order->refundCause = $notes;
            $orderIndex->order->refundNotes = "用户申请";
            $orderIndex->order->afterSaleTime =   date("Y-m-d H:i:s", time());
            $orderIndex->order->userRefundNotes = $notes;
            $orderIndex->order->beforRefundState = $orderIndex->order->state;
            $orderIndex->order->state = 7;
            $orderIndex->order->refundState = 0;
            $orderIndex->order->setLog('用户申请退款，退款金额:' . $orderIndex->order->money . '，原因:' . $notes);
            $orderIndex->order->save();
            if ($orderIndex->save()) {
                DB::commit();
                Event(new StoreMessageEvent($orderIndex->order, 'refundApply'));
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
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($orderId) {
                return $q->unReceived()->where('id', $orderId);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('订单已取消或者已支付');
            }
            $orderIndex->order->receiveTime = date("y-m-d H:i:s", time());
            $orderIndex->order->state =  3;
            $orderIndex->order->save();
            $orderIndex->order->setLog($log);
            if ($orderIndex->order->receivePrint == 1) {
                self::print($orderIndex->order->id);
            }
            if ($orderIndex->order->scene == 2 && in_array(1, $orderIndex->store->takeScreenSetting['score'] ?? [])) {
                TakeScreen::updateOrcreate(['orderSn' => $orderIndex->order->orderSn], [
                    'orderSn' => $orderIndex->order->orderSn,
                    'state' => $orderIndex->order->state,
                    'pickNo' => $orderIndex->order->pickNo,
                    'storeId' => $orderIndex->order->storeId,
                    'uniacid' => $orderIndex->order->uniacid,
                    'orderTime' => $orderIndex->order->created_at,
                    'packaging' => $orderIndex->order->diningType == 1 ? 1 : 0,
                    'diningType' => $orderIndex->order->diningType,
                    'source' => $orderIndex->order->source
                ]);
            }
            Event(new OrderMessageEvent($orderIndex->order, 'receive'));
            Event(new StoreMessageEvent($orderIndex->order, 'receive'));
            //dispatch(new DeliveryCallJob(['uniacid' => $order->uniacid, 'orderSn' => $order->orderSn, 'openid' => $message['userId'] , 'transaction_id' => $message['logNo']]));
            return true;
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }


    /**
     * 制作完成
     */
    public static function maked($orderId, $log = null)
    {
        try {
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($orderId) {
                return $q->making()->where('id', $orderId);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('订单状态不正确');
            }

            $orderIndex->order->makedTime = date("y-m-d H:i:s", time());
            $orderIndex->order->state = 4;
            $orderIndex->order->save();
            if ($orderIndex->order->scene == 1) {
                $orderIndex->order->setLog("商品制作完成，等待配送");
            }
            if ($orderIndex->order->scene == 2) {
                $orderIndex->order->setLog("商品制作完成，等待用户取单");
                Event(new OrderMessageEvent($orderIndex->order, 'takeMeal'));
                TakeScreen::updateOrcreate(['orderSn' => $orderIndex->order->orderSn], [
                    'orderSn' => $orderIndex->order->orderSn,
                    'state' => $orderIndex->order->state,
                    'pickNo' => $orderIndex->order->pickNo,
                    'storeId' => $orderIndex->order->storeId,
                    'uniacid' => $orderIndex->order->uniacid,
                    'orderTime' => $orderIndex->order->created_at,
                    'diningType' => $orderIndex->order->diningType,
                    'source' => $orderIndex->order->source,
                    'packaging' => $orderIndex->order->diningType == 1 ? 1 : 0
                ]);
            }
            return true;
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }


    /**
     * 配送
     */
    public static function delivery($orderId, $deliveryType = 0, $channel = 0)
    {

        try {
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($orderId) {
                return $q->where(function ($q) {
                    return $q->where(function ($q) {
                        return $q->where('scene', 1)->delivery();
                    })->orWhere(function ($q) {
                        return $q->where('scene', 1)->waiting();
                    });
                })->where('id', $orderId);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('订单状态不正确');
            }
            if ($orderIndex->order->state!==4) {
                throw new BadRequestException('订单状态不正确');
            }
            DeliveryService::call($orderId, 2, $channel, $deliveryType);
            // $orderIndex->order->deliveryTime = date("y-m-d H:i:s", time());
            // $orderIndex->order->state = 5;

            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }


    /**
     * 订单完成
     */
    public static function complete($orderId, $log = '')
    {
        try {
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($orderId) {
                return $q->where(function ($q) {
                    return $q->where(function ($q) {
                        return $q->where('scene', 1)->delivery();
                    })->orWhere(function ($q) {
                        return $q->where('scene', 2)->waiting();
                    })->orWhere(function ($q) {
                        return $q->where('scene',30)->where('state',5);
                    });
                })->where('id', $orderId);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('订单状态不正确');
            }
            $orderIndex->state = 6;
            $orderIndex->expiredTime = date("Y-m-d H:i:s", time() + 3600 * 24);
            $orderIndex->order->completionTime = date("y-m-d H:i:s", time());
            $orderIndex->order->state = 6;
            $orderIndex->order->addUserPayStore();
            $orderIndex->order->setLog('用户已取单，订单已完成');
            $orderIndex->order->save();
            $orderIndex->save();
            if ($orderIndex->order->deliveryOrder->channel == 0 && $orderIndex->order->deliveryOrder->deliveryType == 2 && $orderIndex->order->deliveryOrder->callState == 1) {
                $orderIndex->order->deliveryOrder->stateFormat = "订单已送达";
                $orderIndex->order->deliveryOrder->save();
            }
            if ($orderIndex->order->statisticsData) {
                StatisticsDay::where(function ($q) use ($orderIndex) {
                    return $q->where(function ($q) use ($orderIndex) {
                        return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", 0);
                    })->orWhere(function ($q) use ($orderIndex) {
                        return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", $orderIndex->storeId);
                    });
                })->where("day", date("Y-m-d", time()))->update($orderIndex->order->statisticsData);
            }
            $orderIndex->order->user->isPay++;
            $orderIndex->order->user->payTime = date("y-m-d H:i:s", time());
            $orderIndex->order->user->save();
            foreach ($orderIndex->order->goods as $key => $goods) {
                GoodsSpu::where('id', $goods->spuId)->increment('sales', $goods->num);
                Cache::increment("storeGoods:{$goods->storeId}:{$goods->spuId}", $goods->num);
            }
            if ($orderIndex->order->integral > 0) {
                MemberAccountService::changeIntegral($orderIndex->order->userId, 1, $orderIndex->order->integral, MemberAccountLog::INTEGRAL_ORDER_GIVE, 0, "订单{$orderIndex->orderSn}赠送", $orderIndex->orderSn);
            }

            if ($orderIndex->order->exp > 0) {
                MemberAccountService::changeExp($orderIndex->order->userId, 1, $orderIndex->order->exp, MemberAccountLog::EXP_ORDER_GIVE, 0, "订单{$orderIndex->orderSn}赠送", $orderIndex->orderSn);
            }
            Event(new OrderMessageEvent($orderIndex->order, 'complete'));

            $userInfo=Member::where('uniacid', $orderIndex->uniacid)->find($orderIndex->order->userId);
            $uniacid=$orderIndex->order->uniacid;
            $storeId=$orderIndex->order->storeId;
            $userId=$orderIndex->order->userId;
            $partnerId=$userInfo->partnerId;
            $money=$orderIndex->order->money;
            $orderSn=$orderIndex->order->orderSn;
            PartnerOrder::createPartnerOrder($uniacid,$storeId,$userId,$partnerId,$money,$orderSn);
            //Event(new PartnerEvent($orderIndex->order));
            /**
             * 支付有礼
             */
            if ($orderIndex->order->payGiftId > 0) {
                Event(new PayGiftEvent($orderIndex->order, 'pay'));
            }
            /**
             * 集点有礼
             */
            if ($orderIndex->order->collectId > 0) {
                Event(new OrderCollectEvent($orderIndex->order, 'pay'));
            }
            if ($orderIndex->order->user->payOrder == 0) {
                $partyB = PartyB::where('uniacid', $orderIndex->uniacid)
                    ->where('userId', $orderIndex->userId)
                    ->where('firstPayState', 0)
                    ->first();
                if ($partyB) {
                    $partyA = PartyA::where('uniacid', $orderIndex->uniacid)
                        ->where('userId', $partyB->partyAid)
                        ->where('oldWithNewId', $partyB->oldWithNewId)
                        ->first();
                    if ($partyA) {
                        Event(new PartyBEvent($partyB, $partyA, 'firstPay'));
                    }
                }
            }

            TakeScreen::where("orderSn", $orderIndex->orderSn)->update(['state' => $orderIndex->order->state]);
            //dispatch(new OrderStatisticsJob($orderIndex->orderSn));
            StaticService::tongji($orderIndex->orderSn);
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
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

    public static function print($orderId, $orderType = 1)
    {
        $lock_key = "parentOrderId:{$orderId}{$orderType}";
        $is_lock  = Cache::lock($lock_key, 5);
        if (!$is_lock) { // 获取锁权限
            // 防止死锁
            return true;
        }
        try {

            $orderIndex=OrderIndex::whereHas('order', function ($q) use ($orderId) {
                return $q->where('id', $orderId);
            })->first();
            if(!$orderIndex){
                $orderIndex = OrderIndex::whereHas('order', function ($q) use ($orderId) {
                    return $q->where('orderSn', $orderId);
                })->first();
            }





            if (empty($orderIndex)) {
                throw new BadRequestException('订单不存在');
            }
            $printer = Printer::getHardware($orderIndex->uniacid, $orderIndex->storeId);
            if (empty($printer)) {
                return true;
            }
            $orderIndex = $orderIndex->order;
            if (empty($goods)) {
                $goods = !$orderIndex->goods->isEmpty() ? $orderIndex->goods : $orderIndex->subGoods;
            }
            $origin = $orderIndex->orderIndex->type ?: 4;
            foreach ($printer as $v) {
                try {
                    switch ($v['type']) {
                        case 2:
                            if ($v['vendor'] == 'feie') {
                                if ($orderType == 1 || $orderType == 18) {
                                    $printer_type = 7;
                                    $contents = FeieLabelContent::labelAllContent($orderIndex, $goods, $v['rule']);
                                    foreach ($contents as $key => $content) {
                                        $content .= "<DIRECTION>1</DIRECTION>";
                                        $data = Printer::feiPrint($v, $content, 3);
                                        PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '厨房联一菜一单');
                                    }
                                }
                            }
                            if ($v['vendor'] == 'xinye') {
                                $printer_type = 8;
                                $contents = XinyeLabelContent::labelAllContent($orderIndex, $goods, $v['rule']);
                                foreach ($contents as $key => $content) {
                                    $data = Printer::xinyePrint($v, $content, 3);
                                    PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '厨房联一菜一单');
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
                                    $wrap = "<BR>";
                                    break;
                                case 'esLink';
                                    $printer_type = 2;
                                    if ($v['config']['printer_size'] == 2) {
                                        $className = "App\Services\Print\YlyMaxContent";
                                    } else {
                                        $className = "App\Services\Print\YlyContent";
                                    }
                                    $action = 'ylyPrint';
                                    $wrap = "\n";
                                    break;
                                case 'spyun';
                                    $printer_type = 3;
                                    $className = "App\Services\Print\SpyunContent";
                                    $action = 'spyPrint';
                                    $wrap = "<BR>";
                                    break;
                                case 'daqu';
                                    $printer_type = 4;
                                    $className = "App\Services\Print\DaquContent";
                                    $action = 'daquPrint';
                                    $wrap = "<BR>";
                                    break;
                                case 'jiabo';
                                    $printer_type = 5;
                                    $className = "App\Services\Print\JiaboContent";
                                    $action = 'jiaboPrint';
                                    $wrap = "<gpBr/>";
                                    break;
                                case 'xinye';
                                    $printer_type = 6;
                                    if ($v['config']['printer_size'] == 2) {
                                        $className = "App\Services\Print\XinyeMaxContent";
                                    } else {
                                        $className = "App\Services\Print\XinyeContent";
                                    }
                                    $action = 'xinyePrint';
                                    $wrap = "<BR>";
                                    break;
                                default;
                                    $printer_type = 1;
                                    $className = "App\Services\Print\FeieContent";
                                    $action = 'feiPrint';
                                    $wrap = "<BR>";
                                    break;
                            }
                            switch ($orderType) {
                                case 1;
                                    if (isset($v['rule']['config']['qtWmBusiness']) && $v['rule']['config']['qtWmBusiness'] > 0) {
                                        $printGoods = [];
                                        $content = '';
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
                                        if ($orderIndex->scene == 1) {
                                            $content .= $className::instoreCustomerContents($orderIndex, 4, $printGoods);
                                        } else {
                                            $content .= $className::instoreCustomerContents($orderIndex, 14, $printGoods);
                                        }
                                        $num = $v['rule']['config']['qtWmBusiness'] ?? 1;
                                        if ($content) {
                                            if (strlen($content) > 5000) {
                                                $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                            } else {
                                                $data = Printer::$action($v, $content, 2, $num);
                                            }
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '商家联');
                                        }
                                    }
                                    if (isset($v['rule']['config']['qtWmCustomer']) && $v['rule']['config']['qtWmCustomer'] > 0) {
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
                                        if ($orderIndex->scene == 1) {
                                            $content .= $wrap . $className::instoreCustomerContents($orderIndex, 5, $printGoods);
                                        } else {
                                            $content .= $wrap . $className::instoreCustomerContents($orderIndex, 15, $printGoods);
                                        }
                                        $num = $v['rule']['config']['qtWmCustomer'] ?? 1;
                                        if ($content) {
                                            if (strlen($content) > 5000) {
                                                $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                            } else {
                                                $data = Printer::$action($v, $content, 2, $num);
                                            }
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '顾客联');
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
                                        foreach ($printGoods as $vo) {
                                            $content = '';
                                            $content .= $wrap . $className::instoreCustomerContents($orderIndex, 11, [$vo]);
                                            if ($content) {
                                                $data = Printer::$action($v, $content, 2, $num);
                                                PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '厨房联一菜一单');
                                            }
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
                                        $content .= $wrap . $className::instoreCustomerContents($orderIndex, 11, $printGoods);
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
                                    break;
                                case 15;
                                    if (isset($v['rule']['config']['qtWmBusiness']) && $v['rule']['config']['qtWmBusiness'] > 0) {
                                        $printGoods = [];
                                        $content = '';
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
                                        if ($orderIndex->scene == 1) {
                                            $content .= $className::instoreCustomerContents($orderIndex, 4, $printGoods);
                                        } else {
                                            $content .= $className::instoreCustomerContents($orderIndex, 14, $printGoods);
                                        }
                                        $num = $v['rule']['config']['qtWmBusiness'] ?? 1;
                                        if ($content) {
                                            if (strlen($content) > 5000) {
                                                $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                            } else {
                                                $data = Printer::$action($v, $content, 2, $num);
                                            }
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '补打商家联');
                                        }
                                    }
                                    break;
                                case 16;
                                    if (isset($v['rule']['config']['qtWmCustomer']) && $v['rule']['config']['qtWmCustomer'] > 0) {
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
                                        if ($orderIndex->scene == 1) {
                                            $content .= $wrap . $className::instoreCustomerContents($orderIndex, 5, $printGoods);
                                        } else {
                                            $content .= $wrap . $className::instoreCustomerContents($orderIndex, 15, $printGoods);
                                        }
                                        $num = $v['rule']['config']['qtWmCustomer'] ?? 1;
                                        if ($content) {
                                            if (strlen($content) > 5000) {
                                                $data = Printer::$action($v, substr($content, 0, 5000), 2, $num);
                                                $data = Printer::$action($v, substr($content, 5000), 2, $num);
                                            } else {
                                                $data = Printer::$action($v, $content, 2, $num);
                                            }
                                            PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '补打顾客联');
                                        }
                                    }
                                    break;
                                case 17;
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
                                        foreach ($printGoods as $vo) {
                                            $content = '';
                                            $content .= $wrap . $className::instoreCustomerContents($orderIndex, 11, [$vo]);
                                            if ($content) {
                                                $data = Printer::$action($v, $content, 2, $num);
                                                PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, '补打厨房联一菜一单');
                                            }
                                        }
                                    }
                                    break;
                                case 18;
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
                                        $content .= $wrap . $className::instoreCustomerContents($orderIndex, 11, $printGoods);
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
                                    break;
                            }
                            break;
                        default:
                            break;
                    }
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return false;
                }
            }
            optional($is_lock)->release();
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return true;
        }
    }

    //企微机器人推送订单消息
    public static function pushOrder($order, $orderType = 1)
    {
        $config = ConfigService::getStoreConfig('robot_webhook_address', $order->storeId);
        if (empty($config)) {
            return true;
        }
        switch ($orderType) {
            case 1;
                $orderName = '外卖订单';
                break;
            case 2;
                $orderName = '自提订单';
                break;
            case 3;
                $orderName = '店内订单';
                break;
            case 4;
                $orderName = '买单订单';
                break;
            case 5;
                $orderName = '储值订单';
                break;
            case 6;
                $orderName = '积分商城';
                break;
        }
        $orderNo = $order->pickNo;
        $orderSn = $order->orderSn;
        $money = $order->money;
        $payTime = $order->payTime;
        $mobile = $order->mobile;
        $originMoney = $order->goodsMoney;
        $diningTypeFormat = $order->diningTypeFormat;
        $content = [
            'msgtype' => 'markdown',
            'markdown' => [
                'content' => "【" . $diningTypeFormat . "】<font color=\"warning\">" . $orderNo . "</font>" . PHP_EOL . "订单类型：" . $orderName . PHP_EOL . "支付方式：微信支付" . PHP_EOL . "订单号:" . $orderSn . PHP_EOL . "消费合计：" . $originMoney . PHP_EOL . "实收金额：" . $money . PHP_EOL . PHP_EOL . "----------------------------" . PHP_EOL . "下单时间：" . $payTime . PHP_EOL . "下单电话：" . $mobile . PHP_EOL . "--------#完#-------" . PHP_EOL . "[制作完成](http://work.weixin.qq.com/api/doc)                       [订单退款](http://work.weixin.qq.com/api/doc)"
            ]
        ];
        try {
            $url = $config['url'] ?: "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fe2bc246-b8d9-4913-ae27-f5721e391e98";
            $data = httpRequest($url, $content);
        } catch (\Exception $e) {
            file_put_contents('robot_webhook_address.log', $e->getMessage());
        }
    }


    //取酒存酒打印
    public static function otherPrintOrder($orderType, $orderIndex, $str = '')
    {
        switch ($orderType) {
            case 1;
                $name = 'rechargeContents';
                $num = 'czPrintNum';
                $description = '充值订单';
                break;
            case 2;
                $name = 'facepayContents';
                $num = 'dmfPrintNum';
                $description = '当面付';
                break;
            case 3;
                $name = 'drinkLog';
                $num = 'storingWineNum';
                $description = '存取酒';
                break;
        }
        $origin = $orderIndex->orderIndex->type ?: 4;
        $printer = Printer::getHardware($orderIndex->uniacid, $orderIndex->storeId);
        if ($printer) {
            foreach ($printer as $v) {
                $content = '';
                $printer_type = 1;
                $num = $v['rule']['config'][$num];
                if (isset($num) && $num > 0) {
                    switch ($v['vendor']) {
                        case 'feie';
                            if ($v['config']['printer_size'] == 2) {
                                $className = "App\Services\Print\FeieMaxContent";
                            } else {
                                $className = "App\Services\Print\FeieContent";
                            }
                            $content .= $className::$name($orderIndex);
                            $data = Printer::feiPrint($v, $content, 2, $num);
                            break;
                        case 'esLink';
                            if ($v['config']['printer_size'] == 2) {
                                $className = "App\Services\Print\YlyMaxContent";
                            } else {
                                $className = "App\Services\Print\YlyContent";
                            }
                            $content .= $className::$name($orderIndex);
                            $data = Printer::ylyPrint($v, $content, 2, $num);
                            break;
                        case 'spyun';
                            $content .= SpyunContent::$name($orderIndex);
                            $data = Printer::spyPrint($v, $content, 2, $num);
                            break;
                        case 'jiabo';
                            $content .= JiaboContent::$name($orderIndex);
                            $data = Printer::jiaboPrint($v, $content, 2, $num);
                            break;
                        case 'daqu';
                            $content .= DaquContent::$name($orderIndex);
                            $data = Printer::daquPrint($v, $content, 2, $num);
                            break;
                        case 'xinye';
                            if ($v['config']['printer_size'] == 2) {
                                $className = "App\Services\Print\XinyeMaxContent";
                            } else {
                                $className = "App\Services\Print\XinyeContent";
                            }
                            $content .= $className::$name($orderIndex);
                            $data = Printer::xinyePrint($v, $content, 2, $num);
                            break;
                    }
                    PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, $origin, $description);
                }
            }
        }
    }

    public static function partnerBind($storeId,$userId)
    {
        $userInfo=Member::where('id',$userId)->first();
        if($userInfo->partnerId){
             return true;
        }else{
            $userInfo->storeId=$storeId;
            $userInfo->save();
        }
    }
}
