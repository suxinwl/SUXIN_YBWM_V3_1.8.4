<?php

namespace App\Listeners\SignIn;

use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\PayGiftEvent;
use App\Events\SignInEvent;
use App\Models\MemberAccountLog;
use App\Models\MemberSignIn\MemberSignIn;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\PayGift\Receive;
use Illuminate\Support\Facades\DB;

class IntegralGiveListener implements ShouldQueue
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
        $integral = 0;
        if ($model->daily && $model->daily['integralSwitch'] == 1 && $model->daily['integral'] > 0) {
            MemberAccountService::changeIntegral($model->userId, 1, $model->daily['integral'], MemberAccountLog::INTEGRAL_SIGNIN_GIVE, 0, "签到赠送{$model->daily['integral']}积分");
            $integral += $model->daily['integral'];
        }
        if ($model->plusRewards && $model->plusRewards['integralSwitch'] == 1 && $model->plusRewards['integral'] > 0) {
            MemberAccountService::changeIntegral($model->userId, 1, $model->plusRewards['integral'], MemberAccountLog::INTEGRAL_SIGNIN_CONTINUOUS, 0, "连续签到赠送{$model->plusRewards['integral']}积分");
            $integral += $model->plusRewards['integral'];
        }
        MemberSignIn::where("uniacid", $model->uniacid)->where("userId", $model->userId)->update([
            'integral' => DB::raw('`integral` + ' . $integral)
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
