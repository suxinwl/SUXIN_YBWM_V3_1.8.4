<?php

namespace App\Listeners\OrderCollect;

use App\Events\OrderCollectEvent;
use App\Models\MemberAccountLog;
use App\Models\OrderCollect\Receive;
use App\Models\OrderCollect\User;
use App\Services\MemberAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;

class BalanceGiveListener   implements ShouldQueue
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
    public function handle(OrderCollectEvent $event)
    {
        $model = $event->model;
        if ($event->type == 'pay') {
            for ($i = 0; $i < $event->page; $i++) {
                if (empty($model) || empty($model->balanceSwitch) || $model->balance <= 0) {
                    return true;
                }
                MemberAccountService::GiveChange($event->order->userId, 0, $model->balance, MemberAccountLog::BALANCE_ORDERCOLLECT, 0, "集点有礼赠送{$model->balance}余额");
                Receive::updateOrCreate(['orderSn' => $event->order->orderSn], [
                    'collectId' => $model->id,
                    'balance' => DB::raw("`balance` + {$model->balance}"),
                    'issus' => DB::raw("`issus` + {$model->num}"),
                    'userId' => $event->order->userId,
                    'orderId' => 0,
                    'uniacid' => $event->order->uniacid,
                    'orderSn' => $event->order->orderSn
                ]);
                User::where(['collectId' => $model->id, 'userId' => $event->order->userId, 'uniacid' => $model->uniacid])->update([
                    'balance' => DB::raw("`balance` + {$model->balance}")
                ]);
            }
        } elseif ($event->type == 'refund') {
            $receive = $event->receive;
            if ($receive) {
                if ($event->receive->balance > 0) {
                    MemberAccountService::giveRefund($event->order->userId, 0, $event->receive->balance, MemberAccountLog::BALANCE_ORDERCOLLECT_REFUND, 0, "集点有礼奖励撤回");
                }
                User::where(['collectId' => $model->id, 'userId' => $event->order->userId, 'uniacid' => $model->uniacid])->update([
                    'balance' => DB::raw("`balance` - {$receive->balance}"),
                    'issus' => DB::raw("`issus` - {$receive->issus}"),
                    'total' => DB::raw("`total` - {$event->order->collectNum}")
                ]);
            }
            User::where(['collectId' => $model->id, 'userId' => $event->order->userId, 'uniacid' => $model->uniacid])->update([
                'total' => DB::raw("`total` - {$event->order->collectNum}")
            ]);
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
