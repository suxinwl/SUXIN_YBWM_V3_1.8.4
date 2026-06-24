<?php

namespace App\Listeners\SignIn;

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
    public function handle(SignInEvent $event)
    {
        $model = $event->model;
        if (empty($model)) {
            return true;
        }
        $balance = 0;
        if ($model->daily  && $model->daily['balanceSwitch'] == 1 && $model->daily['balance'] > 0) {
            MemberAccountService::GiveChange($model->userId, 0, $model->daily['balance'], MemberAccountLog::BALANCE_SIGNIN_GIVE, 0, "签到赠送{$model->daily['balance']}余额");
            $balance += $model->daily['balance'];
        }
        if ($model->plusRewards && $model->plusRewards['balanceSwitch'] == 1 && $model->plusRewards['balance'] > 0) {
            MemberAccountService::GiveChange($model->userId, 0, $model->plusRewards['balance'], MemberAccountLog::BALANCE_SIGNIN_CONTINUOUS, 0, "连续签到赠送{$model->plusRewards['balance']}余额");
            $balance += $model->plusRewards['balance'];
        }
        MemberSignIn::where("uniacid", $model->uniacid)->where("userId", $model->userId)->update([
            'balance' => DB::raw('`balance` + ' . $balance)
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
