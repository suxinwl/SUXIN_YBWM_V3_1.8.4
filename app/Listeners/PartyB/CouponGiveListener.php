<?php

namespace App\Listeners\PartyB;

use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\PartyBEvent;
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
    public function handle(PartyBEvent $event)
    {
        $model = $event->model;
        $type = $event->type;
        $partyA = $event->partyA;
        if (empty($model)) {
            return true;
        }
        if ($type == 'partyB') {
            if ($model->data['partyB'] && $model->data['partyB']['couponSwitch'] == 1 && !empty($model->data['partyB']['couponData'])) {
                CouponService::issue($model->data['partyB']['couponData'], $model->userId, 17, ['source' => 'oldWithNew:PartyB:' . $model->oldWithNewId]);
            }
            $couponCount = 0;
            if ($model->partyAData && $model->partyAData['couponSwitch'] == 1 && !empty($model->partyAData['couponData'])) {
                CouponService::issue($model->partyAData['couponData'], $partyA->userId, 18, ['source' => 'oldWithNew:PartyA:' . $model->oldWithNewId]);
                $couponCount += collect($model->partyAData['couponData'])->sum('num');
            }
            $partyA->couponCount = $partyA->couponCount + $couponCount;
            $partyA->save();
        } elseif ($type == 'firstPay') {
            if ($model->data['firstPay'] && $model->data['firstPay']['couponSwitch'] == 1 && !empty($model->data['firstPay']['couponData'])) {
                CouponService::issue($model->data['firstPay']['couponData'], $model->userId, 19, ['source' => 'oldWithNew:firstPay:' . $model->oldWithNewId]);
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
