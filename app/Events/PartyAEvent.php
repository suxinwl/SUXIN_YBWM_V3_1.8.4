<?php

namespace App\Events;

use App\Models\MemberSignIn\MemberSignIn;
use App\Models\OldWithNew\PartyA;
use App\Models\PayGift\Receive;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PartyAEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $model;
    public $receive;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(PartyA $model)
    {
        $this->model = $model;
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
