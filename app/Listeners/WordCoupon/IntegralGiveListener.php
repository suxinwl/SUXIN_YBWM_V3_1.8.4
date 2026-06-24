<?php

namespace App\Listeners\WordCoupon;

use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\WordCouponEvent;
use App\Models\MemberAccountLog;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\GiftBig\Receive;

class IntegralGiveListener
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
        if (empty($model) || $model->integral <= 0) {
            return true;
        }
        MemberAccountService::changeIntegral($model->userId, 1, $model->integral, MemberAccountLog::INTEGRAL_WORD_COUPON, 0, "口令红包赠送{$model->integral}积分");
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
