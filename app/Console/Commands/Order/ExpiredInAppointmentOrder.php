<?php

namespace App\Console\Commands\Order;

use App\Events\StoreMessageEvent;
use App\Models\InStore\Order\Order;
use App\Models\Order\TakeOutOrder;
use App\Services\InStoreOrderService;
use App\Services\OrderService;
use Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpiredInAppointmentOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:expiredInAppointmentOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '预订单订单超时逻辑处理';

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
        $start = date("Y-m-d H:i:s", time() - 1800);
        $list = TakeOutOrder::whereIn("state", [2, 3])->where('appointment', 1)->where('serverTime', '<=', $start)->limit(10)->get();
        if (!empty($list)) {
            foreach ($list as $key => $order) {
                Event(new StoreMessageEvent($order, 'appointment'));
            }
        }
    }
}
