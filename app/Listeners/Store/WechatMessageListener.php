<?php

namespace App\Listeners\Store;

use App\Events\MemberRegisteredEvent;
use App\Events\OrderMessageEvent;
use App\Events\StoreMessageEvent;
use App\Models\FollowWechat;
use App\Models\MemberAccountLog;
use App\Models\Wechat;
use App\Services\ConfigService;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class WechatMessageListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\MemberRegisteredEvent  $event
     * @return void
     */
    public function handle(StoreMessageEvent $event)
    {
        try {
            $order = $event->order;
            $msgType = $event->type;
            if ($msgType == 'receive') {
                $msgType = 'newOrder';
            }
            if ($msgType == 'inStoreNewOrder') {
                $msgType = 'newOrder';
            }
            $row = ConfigService::getSystemSet('template_message');
            if (empty($row)) {
                return true;
            }
            $template_id = $row->$msgType;

            if (empty($template_id)) {
                return true;
            }
            //实付金额
            $money =  $order->subOrder->money ?? $order->money ?? '';
            //应付金额
            $sellMoney =  $order->subOrder->sellMoney ?? $order->sellMoney ?? '';
            //取单号
            $pickNo = $order->subOrder->pickNo ?? $order->pickNo;
            //桌位号
            $tableName = $order->subOrder->table->name ?? $order->name;
            //桌位区域
            $tableArea = $order->subOrder->table->area->name ?? $order->table->area->name;
            //桌位类型
            $tableType = $order->subOrder->table->type->name ?? $order->table->area->name;
            $diningType = $order->subOrder->diningType ?? $order->diningType ?? '';
            //订单类型
            if ($order->type == 1 || $order->orderIndex->type == 1 || $order->type == 3) {
                $type =   $order->subOrder->orderTypeFormat ?? $order->orderTypeFormat ?? "";
            } else {
                $type =   $order->subOrder->diningTypeFormat ?? $order->diningTypeFormat ?? "";
                if ($order->subOrder->state == 6) {
                    return true;
                }
                if ($diningType == 4) {
                    $type = $type . '(' . $tableArea . '-' . $tableType . '-' . $tableName . ')';
                }
            }
            //订单状态
            $stateFormat = $order->subOrder->stateFormat ?? $order->stateFormat ?? '';
            //支付时间
            $createTime = $order->payTime ?? $order->orderIndex->payTime ?? '';
            $createTime = $createTime ?: $order->created_at;
            $orderSn = $order->orderSn ?? $order->orderIndex->orderSn ?? '';
            //支付方式
            $payTypeForamt = $order->payTypeFormat ?? $order->orderIndex->payTypeFormat ?? '';
            //订单来源
            $score = $order->scoreFormat ?? $order->orderIndex->scoreFormat ?? '';
            $goodsName = $order->goodsFormat ?? $order->subOrder->goodsFormat;
            $goodsName = mb_strlen($goodsName) > 20 ?  mb_substr($goodsName, 0, 17, 'utf-8') . '...' : $goodsName;
            switch ($msgType) {
                case 'newOrder';
                    $message = Wechat::newOrder($order->store->name, $orderSn, $goodsName, $money, $createTime);
                    break;
                case 'inStoreNewOrder';
                    $message = Wechat::newOrder($order->store->name, $orderSn, $goodsName, $money, $createTime);
                    //$message=Wechat::inStoreNewOrder($tableName,$money,'',$orderSn,$score);
                    break;
                case 'deliveryAbnormal';
                    $reason = collect($order->deliveryOrder->log)->first();
                    $message = Wechat::deliveryAbnormal($order->deliveryOrder->channelName, '', $orderSn, $reason['text'], $money);
                    break;
                case 'refundApply';
                    $message = Wechat::refundApply($order->store->name, $orderSn, $money, '');
                    break;
            }

            $followList = FollowWechat::where(['uniacid' => $order->uniacid, 'storeId' => $order->storeId])->get();

            if ($followList) {
                $app = Wechat::config();
                foreach ($followList as $v) {
                    $meassageData = [
                        'touser' => $v->openId,
                        'template_id' => $template_id,
                        'data' => $message,
                    ];;
                    $res = $app->template_message->send($meassageData);
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return true;
        }
    }

    public function shouldQueue(StoreMessageEvent $event)
    {
        return true;
    }
}
