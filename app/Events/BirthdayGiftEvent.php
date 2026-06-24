<?php

namespace App\Events;

use App\Models\BirthdayPack;
use App\Models\MemberSignIn\MemberSignIn;
use App\Models\PayGift\Receive;
use App\Services\ConfigService;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BirthdayGiftEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $model;
    public $type;
    public $receive;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($model, $type)
    {
        $this->model = $model;
        $this->type = $type;
        $config = ConfigService::getChannelConfig('birthdayGift', $model->uniacid, $model->storeId);
        if ($type == "perfect") {
            $receive = BirthdayPack::where('uniacid', $model->uniacid)->where('type', 1)
                ->where('storeId', $model->storeId)
                ->where('userId', $model->id)->first();
            if ($receive) {
                $this->receive = null;
            } else {
                $this->receive = BirthdayPack::updateOrcreate([
                    'uniacid' => $model->uniacid,
                    'userId' => $model->id,
                    'storeId' => $model->storeId,
                    'year' => Carbon::now()->format("Y"),
                    'data' => $config['perfect'],
                    'type' => 1
                ]);
            }
        } elseif ($type == "birthday") {
            $receive = BirthdayPack::where('uniacid', $model->uniacid)->where('type', 2)->where('userId', $model->id)->first();
            if ($receive) {
                $this->receive = null;
            } else {
                $this->receive = BirthdayPack::updateOrcreate([
                    'uniacid' => $model->uniacid,
                    'userId' => $model->id,
                    'storeId' => $model->storeId,
                    'year' => Carbon::now()->format("Y"),
                    'data' => $config['birthday'],
                    'type' => 2
                ]);
            }
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
