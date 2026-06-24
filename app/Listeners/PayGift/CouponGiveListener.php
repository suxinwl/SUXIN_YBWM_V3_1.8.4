<?php

namespace App\Listeners\PayGift;

use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\PayGiftEvent;
use App\Models\Coupon\MemberCoupon;
use App\Models\MemberAccountLog;
use App\Services\CouponService;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\PayGift\Receive;

class CouponGiveListener  implements ShouldQueue
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
            if (empty($payGift) || empty($payGift->couponSwitch) || empty($payGift->couponGive)) {
                return true;
            }
            CouponService::issue($payGift->couponGive, $order->userId, 5, ['source' => 'orderId:' . $order->id]);
            Receive::updateOrCreate(['orderSn' => $order->orderSn], [
                'payGiftId' => $payGift->id,
                'couponGive' => collect($payGift->couponGive)->pluck('id')->all(),
                'userId' => $order->userId,
                'storeId' => $order->storeId,
                'uniacid' => $order->uniacid,
                'orderSn' => $order->orderSn,
                'orderId' => $order->id
            ]);
        } elseif ($event->type == 'refund') {
            $receive = $event->receive;
            if ($receive) {
                MemberCoupon::where('userId', $order->userId)
                    ->where('state', 1)
                    ->where('channel', 5)
                    ->whereIn('couponId', $receive->couponGive)
                    ->where('source', 'orderId:' . $order->id)
                    ->update(['state' => 3, 'updated_at' => date("Y-m-d H:i:s", time())]);
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
