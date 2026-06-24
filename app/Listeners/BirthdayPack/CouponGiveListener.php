<?php

namespace App\Listeners\BirthdayPack;

use App\Events\BirthdayGiftEvent;
use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\PayGiftEvent;
use App\Events\SignInEvent;
use App\Models\Coupon\MemberCoupon;
use App\Models\MemberAccountLog;
use App\Models\MemberSignIn\MemberSignIn;
use App\Services\CouponService;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\PayGift\Receive;
use Illuminate\Support\Facades\DB;

class CouponGiveListener implements ShouldQueue
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
    public function handle(BirthdayGiftEvent $event)
    {
        $model = $event->receive;
        if (empty($model)) {
            return true;
        }
        if ($model->data && $model->data['switch'] && $model->data['couponSwitch'] == 1 && !empty($model->data['couponList'])) {
            $log = $model->type == 1 ? 15 : 16;
            CouponService::issue($model->data['couponList'], $model->userId, $log);
        }
    }


    /**
     * 确定监听器是否应加入队列。
     *
     * @param  \App\Events\OrderCreated  $event
     * @return bool
     */
    public function shouldQueue(BirthdayGiftEvent $event)
    {
        return $event->receive;
    }
}
