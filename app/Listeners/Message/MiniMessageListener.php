<?php

namespace App\Listeners\Message;

use App\Events\MemberRegisteredEvent;
use App\Events\OrderMessageEvent;
use App\Jobs\SendMiniMessage;
use App\Models\ApplyMessage;
use App\Models\MemberAccountLog;
use App\Services\MemberAccountService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\SwooleJobService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class MiniMessageListener  implements ShouldQueue
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
    public function handle(OrderMessageEvent $event)
    {
        try {
            $order = $event->order;
            $type = $event->type;
            $model = ApplyMessage::where("uniacid", $order->uniacid)->where("type", $type)->where("state", 1)->first();
            if ($model) {
                $model->send($order);
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
    public function shouldQueue()
    {
        return true;
    }
}
