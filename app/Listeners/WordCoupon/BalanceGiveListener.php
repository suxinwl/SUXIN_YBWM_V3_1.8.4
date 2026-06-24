<?php

namespace App\Listeners\WordCoupon;

use App\Events\MemberRegisteredEvent;
use App\Events\WordCouponEvent;
use App\Models\GiftBig\Receive;
use App\Models\MemberAccountLog;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class BalanceGiveListener
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
        Log::error($model);
        if (empty($model) ||  $model->balance <= 0) {
            return true;
        }
        MemberAccountService::GiveChange($model->userId, 0, $model->balance, MemberAccountLog::BALANCE_WORD_COUPON, 0, "口令礼包赠送{$model->balance}余额");
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
