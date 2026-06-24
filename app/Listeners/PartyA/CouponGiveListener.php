<?php

namespace App\Listeners\SignIn;

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
    public function handle(SignInEvent $event)
    {
        $model = $event->model;
        if (empty($model)) {
            return true;
        }
        $couponCount = 0;
        if ($model->daily && $model->daily['couponSwitch'] == 1 && !empty($model->daily['couponList'])) {
            CouponService::issue($model->daily['couponList'], $model->userId, 8, ['source' => 'singIn:' . $model->id]);
            $couponCount += collect($model->daily['couponList'])->sum('num');
        }
        if ($model->plusRewards && $model->plusRewards['couponSwitch'] == 1 && !empty($model->plusRewards['couponList'])) {
            CouponService::issue($model->plusRewards['couponList'], $model->userId, 8, ['source' => 'singIn:' . $model->id]);
            $couponCount += collect($model->plusRewards['couponList'])->sum('num');
        }
        MemberSignIn::where("uniacid", $model->uniacid)->where("userId", $model->userId)->update([
            'couponCount' => DB::raw('`couponCount` + ' . $couponCount)
        ]);
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
