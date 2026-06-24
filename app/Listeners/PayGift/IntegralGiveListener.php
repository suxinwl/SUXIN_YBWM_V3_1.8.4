<?php

namespace App\Listeners\PayGift;

use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\PayGiftEvent;
use App\Models\MemberAccountLog;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\PayGift\Receive;

class IntegralGiveListener  implements ShouldQueue
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
    public function handle(PayGiftEvent $event)
    {
        $payGift = $event->model->payGift;
        $order = $event->model;
        if ($event->type == 'pay') {
            if (empty($payGift) || empty($payGift->integralSwitch) || $payGift->integral <= 0) {
                return true;
            }
            MemberAccountService::changeIntegral($order->userId, 1, $payGift->integral, MemberAccountLog::INTEGRAL_PAYGIFT, 0, "支付有礼赠送{$payGift->integral}积分");
            Receive::updateOrCreate(['orderSn' => $order->orderSn], [
                'payGiftId' => $payGift->id,
                'integral' => $payGift->integral,
                'userId' => $order->userId,
                'storeId' => $order->storeId,
                'uniacid' => $order->uniacid,
                'orderSn' => $order->orderSn,
                'orderId' => $order->id
            ]);
        } elseif ($event->type == 'refund') {
            $receive = $event->receive;
            if ($receive && $payGift->integral > 0) {
                MemberAccountService::changeIntegral($order->userId, 2, $payGift->integral, MemberAccountLog::INTEGRAL_PAYGIFT_REFUND, 0, "支付有礼奖励积分撤回");
            }
        }
    }

    /**
     * 确定监听器是否应加入队列。
     *
     * @param  \App\Events\OrderCreated  $event
     * @return bool
     */
    public function shouldQueue()
    {
        return true;
    }
}
