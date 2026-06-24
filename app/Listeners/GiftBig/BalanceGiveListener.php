<?php

namespace App\Listeners\GiftBig;

use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Models\GiftBig\Receive;
use App\Models\MemberAccountLog;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
    public function handle(MemberGiftBigEvent $event)
    {
        $model = $event->model;
        if (empty($model) || empty($model->balanceSwitch) || $model->balance <= 0) {
            return true;
        }
        MemberAccountService::GiveChange($event->member->id, 0, $model->balance, MemberAccountLog::BALANCE_GIFT_BIG, 0, "新人礼包赠送{$model->balance}余额");
        Receive::updateOrCreate(['bigId' => $model->id, 'userId' => $event->member->id], [
            'bigId' => $model->id,
            'userId' => $event->member->id,
            'uniacid'=>$model->uniacid,
            'balance' =>$model->balance?:0.00,
            'data' => $model,
            'storeId'=>$model->storeId
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
