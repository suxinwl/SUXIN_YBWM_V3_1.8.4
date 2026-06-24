<?php

namespace App\Events;

use App\Models\GiftBig\GiftBig;
use App\Models\WordCoupon\Receive;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WordCouponEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $model;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Receive $receive)
    {
        $this->model =  $receive;
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
