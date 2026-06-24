<?php

namespace App\Listeners\Partner;

use App\Events\CouponEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\OrderMessageEvent;
use App\Events\PartnerEvent;
use App\Jobs\SendMiniMessage;
use App\Models\ApplyMessage;
use App\Models\MemberAccountLog;
use App\Models\PartnerOrder;
use App\Services\MemberAccountService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\SwooleJobService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;


class RefundListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\MemberRegisteredEvent  $event
     * @return void
     */
    public function handle(PartnerEvent $event)
    {
        try {
            $order = $event->model;
            $partners  = PartnerOrder::where('orderSn', $order->orderSn)->get();
            if ($partners) {
                foreach ($partners as $key => $partnerOrder) {

                    $res =  MemberAccountService::subCanWithdrawalAmount($partnerOrder->partnerId, $partnerOrder->money, 0, $partnerOrder->orderSn,'订单退款');;
                    if ($res) {
                        $partnerOrder->state = $order->state;
                        $partnerOrder->isRefund = 1;
                        $partnerOrder->save();
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return true;
        }
    }

    /**
     * 确定监听器是否应加入队列。
     *
     * @param  \App\Events\OrderCreated  $event
     * @return bool
     */
    public function shouldQueue(PartnerEvent $event)
    {
        return $event->model->state == 8;
    }
}
