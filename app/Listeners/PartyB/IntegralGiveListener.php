<?php

namespace App\Listeners\PartyB;

use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\PartyBEvent;
use App\Events\PayGiftEvent;
use App\Events\SignInEvent;
use App\Models\MemberAccountLog;
use App\Models\MemberSignIn\MemberSignIn;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\PayGift\Receive;
use Illuminate\Support\Facades\DB;

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
    public function handle(PartyBEvent $event)
    {
        $model = $event->model;
        $type = $event->type;
        $partyA = $event->partyA;
        if (empty($model)) {
            return true;
        }
        if ($type == 'partyB') {
            if ($model->data['partyB'] && $model->data['partyB']['integralSwitch'] == 1 && $model->data['partyB']['integral'] > 0) {
                MemberAccountService::changeIntegral($model->userId, 1, $model->data['partyB']['integral'], MemberAccountLog::INTEGRAL_OLDWITHNEW_PARTYB, 0, "老带新被邀请赠送{$model->data['partyB']['integral']}积分");
            }
            $model->partyBstate = 1;
            $model->save();
            $integral = 0;
            if ($model->partyAData && $model->partyAData['integralSwitch'] == 1 && $model->partyAData['integral'] > 0) {
                MemberAccountService::changeIntegral($partyA->userId, 1, $model->partyAData['integral'], MemberAccountLog::INTEGRAL_OLDWITHNEW_PARTYA, 0, "老带新邀请新人赠送{$model->partyAData['integral']}积分");
                $integral += $model->partyAData['integral'];
            }
            $partyA->integral = $partyA->integral + $integral;
            $partyA->exchangeCount = $partyA->exchangeCount + $model->partyAData['person'];
            $partyA->save();
        } elseif ($type == 'firstPay') {
            if ($model->data['firstPay'] && $model->data['firstPay']['integralSwitch'] == 1 && $model->data['firstPay']['integral'] > 0) {
                MemberAccountService::changeIntegral($model->userId, 1, $model->data['firstPay']['integral'], MemberAccountLog::INTEGRAL_OLDWITHNEW_FIRSTPAY, 0, "老带新首次下单赠送{$model->data['firstPay']['integral']}积分");
            }
            $model->firstPayState = 1;
            $model->save();
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
