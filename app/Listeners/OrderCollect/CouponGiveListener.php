<?php

namespace App\Listeners\OrderCollect;

use App\Events\OrderCollectEvent;
use App\Models\Coupon\MemberCoupon;
use App\Models\OrderCollect\Receive;
use App\Models\OrderCollect\User;
use App\Services\CouponService;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
class CouponGiveListener  implements ShouldQueue
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
            if (empty($model) || empty($model->couponSwitch) || empty($model->couponGive)) {
                return true;
            }
            for ($i = 0; $i < $event->page; $i++) {
                CouponService::issue($model->couponGive, $event->order->userId, 6, ['source' => 'orderId:' . $event->order->id]);
                Receive::updateOrCreate(['orderSn' => $event->order->orderSn], [
                    'collectId' => $model->id,
                    'couponGive' => collect($model->couponGive)->pluck('id')->all(),
                    'couponCount' => collect($model->couponGive)->pluck('num')->sum(),
                    'userId' => $event->order->userId,
                    'orderId' => 0,
                    'uniacid' => $event->order->uniacid,
                    'orderSn' => $event->order->orderSn
                ]);
                User::where(['collectId' => $model->id, 'userId' => $event->order->userId, 'uniacid' => $model->uniacid])->update([
                    'couponCount' => DB::raw("`couponCount` + " . collect($model->couponGive)->pluck('num')->sum())
                ]);
            }
        } elseif ($event->type == 'refund') {
            $receive = $event->receive;
            if ($receive) {
                MemberCoupon::where('userId', $event->order->userId)
                    ->where('state', 1)
                    ->where('channel', 6)
                    ->whereIn('couponId', $receive->couponGive)
                    ->where('source', 'orderId:' . $event->order->id)
                    ->update(['state' => 3, 'updated_at' => date("Y-m-d H:i:s", time())]);
                User::where(['collectId' => $model->id, 'userId' => $event->order->userId, 'uniacid' => $model->uniacid])->update([
                    'couponCount' => DB::raw("`couponCount` - {$receive->couponCount}"),
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
