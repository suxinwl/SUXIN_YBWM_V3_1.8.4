<?php

namespace App\Events;

use App\Models\MemberSignIn\MemberSignIn;
use App\Models\OldWithNew\PartyA;
use App\Models\OldWithNew\PartyB;
use App\Models\PayGift\Receive;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use function Yansongda\Supports\value;

class PartyBEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $model;
    public $type;
    public $partyA;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(PartyB $model, PartyA $partyA, $type)
    {
        if ($type == 'partyB') {
            $activity = $partyA->activity;
            $partyA->partyBCount++;
            if ($activity->partyA['type'] == 1 && $model->exchangeCount == 0) {
                $num = bcdiv(($partyA->partyBCount - $partyA->exchangeCount), $activity->partyA['giveData']['person']);
                if ($num > 0) {
                    $model->partyAData = $activity->partyA['giveData'];
                }
            } elseif ($activity->partyA['type'] == 2) {
                $num = bcdiv(($partyA->partyBCount - $partyA->exchangeCount), $activity->partyA['giveData']['person']);
                if ($num > 0) {
                    $model->partyAData = $activity->partyA['giveData'];
                }
            } elseif ($activity->partyA['type'] == 3) {
                collect($activity->partyA['giveData'])->each(function ($give) use ($model, $partyA) {
                    if ($partyA->partyBCount == $give['person']) {
                        $model->partyAData = $give;
                    }
                });
            }
            $model->data = $activity->partyB['partyB']['switch'] == 1 ? $activity->partyB : null;
            $model->save();
            $partyA->save();
        }
        $this->model = $model;
        $this->type = $type;
        $this->partyA = $partyA;
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
