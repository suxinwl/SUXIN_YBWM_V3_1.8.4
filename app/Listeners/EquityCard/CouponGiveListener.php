<?php

namespace App\Listeners\EquityCard;

use App\Events\BirthdayGiftEvent;
use App\Events\EquityCardEvent;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
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
    public function handle(EquityCardEvent $event)
    {
        $model = $event->model;
        if (empty($model) || $model->endTime < Carbon::now()->toDateTimeString() || !$model->equityCard->couponSwitch) {
            return true;
        }
        if (empty($model->nextTime)) {
            if (!empty($model->equityCard->couponGive)) {
                CouponService::issue(collect($model->equityCard->couponGive)->toArray(), $model->userId, 21);
            }
            $model->nextTime = Carbon::now()->addDays($model->equityCard->periodCouponGive['periodDay'])->toDateTimeString();
        } else {
            $member=Member::where(['id'=>$model->userId])->first();
            if($member){
                if ($model->equityCard->periodCouponGive && $model->equityCard->periodCouponGive['switch'] == 1 && !empty($model->equityCard->periodCouponGive['couponGive'])) {
                    CouponService::issue(collect($model->equityCard->periodCouponGive['couponGive'])->toArray(), $model->userId, 22);
                }
            }
            $model->nextTime = Carbon::now()->addDays($model->equityCard->periodCouponGive['periodDay'])->toDateTimeString();
        }
        $model->save();
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
