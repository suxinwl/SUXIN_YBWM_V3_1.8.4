<?php

namespace App\Console\Commands\Order;

use App\Models\InStore\Order\Order;
use App\Services\InStoreOrderService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpiredInstoreOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:expiredInstoreOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '店内订单超时逻辑处理';

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
        $list = Order::whereIN('diningType', [5, 6])->where("expiredTime", '<=', $start)->limit(10)->get();
        if (!empty($list)) {
            foreach ($list as $key => $order) {
                try {
                    switch ($order->state) {
                        case 1:
                            break;
                        case 2:
                            break;
                        case 3:
                            InStoreOrderService::maked($order->id); //制作时间超时自动完成制作
                            break;
                        case 4:
                            InStoreOrderService::complete($order->id); //制作时间超时自动完成制作
                            break;
                        case 5:
                            break;
                        case 7:
                            break;
                        default:
                            true;
                    }
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    false;
                }
            }
        }
    }
}
