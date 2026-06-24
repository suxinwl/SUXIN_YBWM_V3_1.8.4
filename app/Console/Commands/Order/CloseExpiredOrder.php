<?php

namespace App\Console\Commands\Order;

use App\Jobs\Order\CloseExpiredOrderJob;
use App\Models\Order\OrderIndex;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseExpiredOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:expiredPay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '订单超时逻辑处理';

    /**
     * Create a new command instance.
     *
     * @ void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @ int
     */
    public function handle()
    {
        $start = date("Y-m-d H:i:59", time());
        $list = OrderIndex::whereHas('order', function ($q) use ($start) {
            $q->whereIn('state', [1, 2, 3, 4, 5, 6, 7])->where("expiredTime", '<=', $start)->whereNotNull('expiredTime');
        })->whereIn('state', [1, 2])->limit(50)->orderBy('id', 'desc')->get();
        if (!empty($list)) {
            foreach ($list as $key => $order) {
                try {
                    switch ($order->order->state) {
                        case 1:
                            OrderService::close($order->order->id, '订单支付超时,系统自动关闭订单'); //待支付超时自动关闭
                            break;
                        case 2:
                            OrderService::refund($order->order->id, $order->order->money, 0, '门店超时未接单，系统自动退款'); //待接单超时自动退款
                            break;
                        case 3:
                            OrderService::maked($order->order->id); //制作时间超时自动完成制作
                            break;
                        case 4:
                            if ($order->order->scene == 1) {
                                OrderService::delivery($order->order->id); //待配送超时自动配送
                            } else {
                                OrderService::complete($order->order->id); //店内订单配送超时自动完成
                            }
                            break;
                        case 5:
                            OrderService::complete($order->order->id); //配送中 自动完成
                            break;
                        case 7:
                            //  OrderService::refund($order->order->id, $order->order->money, 0, '', '配送超时,系统自动退款'); //申请退款超时未处理自动退款
                            break;
                        default:
                            '';
                    }
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
            }
        }
    }
}
