<?php

namespace App\Services;

use AlibabaCloud\Config\V20190108\PutDeliveryChannel;
use App\Events\OrderMessageEvent;
use App\Events\StoreMessageEvent;
use App\Models\ChannelConfig;
use App\Models\TopLevel;
use App\Traits\ResourceTrait;
use App\Models\Config;
use App\Models\Delivery\Channel;
use App\Models\Delivery\Order;
use App\Models\OpenWechatAuth;
use App\Models\Order\TakeOutOrder;
use App\Models\StoreConfig;
use App\Models\TakeOut\Delivery;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class DeliveryService
{
    public static  function call($orderId, $callType = 1, $channel = 0, $deliveryType = 0)
    {
        try {
            $order = TakeOutOrder::where('scene', 1)->find($orderId);
            if (empty($order)) {
                throw new BadRequestException('订单不存在');
            }
            $delvery = Order::where("uniacid", $order->uniacid)->where("orderSn", $order->orderSn)->first();
            if (empty($delvery)) {
                $delvery = new  Order([
                    'storeId' => $order->storeId,
                    'uniacid' => $order->uniacid,
                    'orderSn' => $order->orderSn,
                    'callType' => $callType,
                    'orderMoney' => $order->deliveryMoney,
                    'appointment' => 0,
                    'callNum' => 0,
                    'deliveryIndex' => 0,
                    'deliveryType' => $deliveryType,
                    'loseType' => $order->deliveryStoreRule->loseType,
                    'loseNum' => $order->deliveryStoreRule->loseNum,
                    'deliveryData' => $order->deliveryStoreRule->deliveryData,
                    'startAddress' => ['contact' => $order->store->contact, 'tel' => $order->store->storeMobile, 'address' => $order->store->address, 'lat' => $order->store->lat, 'lng' => $order->store->lng],
                    'endAddress' => $order->address
                ]);
                $delvery->setLog("【系统已处理】系统创建配送任务成功，将于" . date("Y-m-d H:i:s") . "发起配送");
            } else {
                $delvery->callType = $callType;
                $delvery->deliveryType = $deliveryType;
            }
            if ($callType != 1) {
                $delvery->deliveryIndex = 0;
            }
            $delvery->channel = $channel;
            $delvery->call();
            $delvery->save();
            if ($delvery->callState == 2) {
                $delvery->order->deliveryCollTime = null;
                $delvery->order->save();
                Event(new StoreMessageEvent($delvery->order, 'deliveryAbnormal'));
            } else {
                $delvery->order->deliveryCollTime = null;
                $delvery->order->deliveryTime = date("Y-m-d H:i:s", time());
                $delvery->order->state = 5;
                $delvery->order->save();
                $delvery->order->setLog($delvery->channelName . '配送中');
                Event(new OrderMessageEvent($delvery->order, 'delivery'));
            }
            return true;
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
        }
    }
}
