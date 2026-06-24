<?php

namespace App\Listeners\VipGive;

use App\Events\MemberRegisteredEvent;
use App\Events\OrderMessageEvent;
use App\Models\ApplyMessage;
use App\Models\MemberAccountLog;
use App\Services\MemberAccountService;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SmsMessageListener implements ShouldQueue
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
    public function handle(MemberRegisteredEvent $event)
    {
        try {
            $order = $event->order;
            $type = $event->type;
            $model = ApplyMessage::where("uniacid", $order->uniacid)->where("type", 'smsVipChange')->where("state", 1)->first();
            if ($model) {
                $model->send($order);
            }
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return true;
        }
    }
}
