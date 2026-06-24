<?php

namespace App\Listeners\WordCoupon;

use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\WordCouponEvent;
use App\Models\MemberAccountLog;
use App\Services\CouponService;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\GiftBig\Receive;

class CouponGiveListener 

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
    public function handle(WordCouponEvent $event)
    {
        $model = $event->model;
        if (empty($model) || empty($model->coupon)) {
            return true;
        }
        CouponService::issue($model->coupon, $model->userId, 20);
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
