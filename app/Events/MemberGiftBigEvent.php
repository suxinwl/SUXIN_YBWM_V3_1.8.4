<?php

namespace App\Events;

use App\Models\GiftBig\GiftBig;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MemberGiftBigEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $model;
    public $member;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($member)
    {
        $userId = $member->id;
        $uniacid = $member->uniacid;
        $storeId = $member->storeId;
        $this->member = $member;
        $this->model =  GiftBig::where('uniacid', $uniacid)
            ->where('storeId', $storeId)
            ->whereDoesntHave('receives', function ($q) use ($userId) {
                return $q->where('userId', $userId);
            })
            ->where('startTime', '<=', date("Y-m-d H:i:s"))
            ->where('endTime', '>=', date("Y-m-d H:i:s"))
            ->first();
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
