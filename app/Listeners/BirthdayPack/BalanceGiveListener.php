<?php

namespace App\Listeners\BirthdayPack;

use App\Events\BirthdayGiftEvent;
use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\PayGiftEvent;
use App\Events\SignInEvent;
use App\Models\MemberAccountLog;
use App\Models\MemberSignIn\MemberSignIn;
use App\Models\PayGift\PayGift;
use App\Models\PayGift\Receive;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class BalanceGiveListener implements ShouldQueue
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
        if ($model->data && $model->data['switch'] && $model->data['balanceSwitch'] == 1 && $model->data['balance'] > 0) {
            $log = $model->type == 1 ? MemberAccountLog::INTEGRAL_BIRTHDAYGIFT_PERFECT : MemberAccountLog::INTEGRAL_BIRTHDAYGIFT_BIRTHDA;
            $notes = $model->type == 1 ? "完善资料赠送{$model->data['balance']}余额" : "生日有礼赠送{$model->data['balance']}余额";
            MemberAccountService::GiveChange($model->userId, 0, $model->data['balance'], $log, 0, $notes);
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
