<?php

namespace App\Events;

use App\Models\OrderCollect\Receive;
use App\Models\OrderCollect\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderCollectEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $model;
    public $type;
    public $user;
    public $receive;
    public $page = 0;
    public $order;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($order, $type)
    {
        $this->order = $order;
        $this->model = $order->orderCollect;
        $this->type = $type;
        $this->receive = Receive::where('orderId', $order->id)->first();
        $user = User::updateOrCreate(
            [
                'uniacid' => $order->uniacid,
                'userId' => $order->userId,
                'collectId' => $this->model->id
            ],
            [
                'uniacid' => $order->uniacid,
                'userId' => $order->userId,
                'collectId' => $this->model->id,
            ]
        );
        if ($this->type == 'pay') {
            $user->total = $user->total + $order->collectNum;
            $user->save();
        }
        if ($this->model->num <=  $user->total - $user->issus) {
            $total = $user->total - $user->issus;
            $this->page = intval(($user->total - $user->issus) / $this->model->num);
            User::where([
                'collectId' => $this->model->id,
                'userId' => $order->userId,
                'uniacid' => $order->uniacid
            ])->update([
                'issus' => DB::raw("`issus` + {$total}")
            ]);
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
