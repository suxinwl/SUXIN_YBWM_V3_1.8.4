<?php

namespace App\Listeners\PayGift;

use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\PayGiftEvent;
use App\Models\MemberAccountLog;
use App\Models\PayGift\PayGift;
use App\Models\PayGift\Receive;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BalanceGiveListener  implements ShouldQueue
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
            if (empty($payGift) || empty($payGift->balanceSwitch) || $payGift->balance <= 0) {
                return true;
            }
            MemberAccountService::GiveChange($order->userId, 0, $payGift->balance, MemberAccountLog::BALANCE_PAYGIFT, 0, "支付有礼赠送{$payGift->balance}余额");
            Receive::updateOrCreate(['orderSn' => $order->orderSn], [
                'uniacid' => $payGift->uniacid,
                'storeId' => $order->storeId,
                'userId' => $order->userId,
                'payGiftId' => $payGift->id,
                'balance' => $payGift->balance,
                'orderSn' => $order->orderSn,
                'orderId' => $order->id
            ]);
        } elseif ($event->type == 'refund') {
            $receive = $event->receive;
            if ($receive && $payGift->balance > 0) {
                MemberAccountService::giveRefund($order->userId, 0, $payGift->balance, MemberAccountLog::BALANCE_PAYGIFT_REFUND, 0, "支付有礼奖励撤回");
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
