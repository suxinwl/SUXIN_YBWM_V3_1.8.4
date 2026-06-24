<?php

namespace App\Listeners\OrderCollect;

use App\Events\OrderCollectEvent;
use App\Models\MemberAccountLog;
use App\Models\OrderCollect\User;
use App\Services\MemberAccountService;
use App\Models\OrderCollect\Receive;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;

class IntegralGiveListener  implements ShouldQueue
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
            if (empty($model) || empty($model->integralSwitch) || $model->integral <= 0) {
                return true;
            }
            for ($i = 0; $i < $event->page; $i++) {
                MemberAccountService::changeIntegral($event->order->userId, 1, $model->integral, MemberAccountLog::INTEGRAL_ORDERCOLLECT, 0, "集点有礼赠送{$model->integral}积分");
                Receive::updateOrCreate(['orderSn' => $event->order->orderSn], [
                    'collectId' => $model->id,
                    'integral' => DB::raw("`integral` + {$model->integral}"),
                    'orderId' => 0,
                    'userId' => $event->order->userId,
                    'uniacid' => $event->order->uniacid,
                    'orderSn' => $event->order->orderSn
                ]);
                User::where(['collectId' => $model->id, 'userId' => $event->model->userId, 'uniacid' => $model->uniacid])->update([
                    'integral' => DB::raw("`integral` + {$model->integral}")
                ]);
            }
        } elseif ($event->type == 'refund') {
            $receive = $event->receive;
            if ($receive && $event->receive->integral > 0) {
                MemberAccountService::changeIntegral($event->order->userId, 2, $event->receive->integral, MemberAccountLog::INTEGRAL_ORDERCOLLECT_REFUND, 0, "集点有礼奖励撤回");
                User::where(['collectId' => $model->id, 'userId' => $event->order->userId, 'uniacid' => $model->uniacid])->update([
                    'integral' => DB::raw("`integral` - {$receive->integral}")
                ]);
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
