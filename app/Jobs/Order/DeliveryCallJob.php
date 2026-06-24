<?php

namespace App\Jobs\Order;

use App\Services\DeliveryService;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Mail;

class DeliveryCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $orderId;
    public $callType;
    public $channel;
    public $deliveryType;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id = 0, $callType = 0, $channel = 0, $deliveryType = 0)
    {
        $this->orderId =  $id;
        $this->callType =  $callType;
        $this->channel =  $channel;
        $this->deliveryType = $deliveryType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            DeliveryService::call($this->orderId, $this->callType, $this->channel, $this->deliveryType);
        } catch (\Exception $e) {
            file_put_contents('DeliveryCallJob.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }
}
