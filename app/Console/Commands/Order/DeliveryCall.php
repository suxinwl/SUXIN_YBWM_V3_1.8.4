<?php

namespace App\Console\Commands\Order;

use App\Models\Delivery\Order;
use App\Models\Order\OrderIndex;
use App\Models\Order\TakeOutOrder;
use App\Models\TakeOut\Delivery;
use App\Services\DeliveryService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeliveryCall extends Command
{
    protected $signature = 'order:deliveryCall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '订单自动配送规则';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $start = date("Y-m-d H:i:s", time());
        $list = TakeOutOrder::where('scene', 1)->whereIn('state', [2,3,4])->where("deliveryCollTime", '<=', $start)->get();
        foreach ($list as  $key => $order) {
            try {
                DeliveryService::call($order->id, 1, 0, $order->deliveryStoreRule->deliveryType);
            } catch (\Exception $e) {
                Log::error( $e->getMessage());
            }
        }
    }
}
