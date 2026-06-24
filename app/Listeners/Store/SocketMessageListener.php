<?php

namespace App\Listeners\Store;

use App\Events\MemberRegisteredEvent;
use App\Events\OrderMessageEvent;
use App\Events\StoreMessageEvent;
use App\Models\ApplyMessage;
use App\Models\MemberAccountLog;
use App\Models\VoiceMessage;
use App\Services\ConfigService;
use App\Services\MemberAccountService;
use App\Services\SmsService;
use App\Services\SwooleJobService;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SocketMessageListener implements ShouldQueue
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
    public function handle(StoreMessageEvent $event)
    {
        try {
            try {
                $order = $event->order;
                $type = $event->type;
                if ($order->score != 1) {
                    if (in_array($type, ['newOrder', 'inStoreNewOrder'])) {
                        if ($order->subOrder->diningType == 6) {
                            $type = 'receive';
                        }
                    }
                    if ($type == "complete") {
                        if ($order->subOrder->diningType == 4 && $order->subOrder->payType == 2) {
                            $type = 'complete';
                        } else {
                            $type = null;
                        }
                    }
                }
                $store = DB::table('store')->find($order->storeId);
                $storeId = $store->isolate == 1 ? $store->id : 0;
                $voiceMessage = VoiceMessage::where('uniacid', $order->uniacid)
                    ->where('storeId', $storeId)
                    ->where("type", $type)->first();
                if (empty($voiceMessage) || $voiceMessage->num <= 0) {
                    return true;
                }
                $msg = [
                    "type" => "voice",
                    "msg" => $voiceMessage,
                ];
                SwooleJobService::sendMessage($order->uniacid, $order->storeId, json_encode($msg, 320));
            } catch (\Exception $e) {
                Log::info($e->getMessage());
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
