<?php

namespace App\Jobs\Order;

use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Mail;

class CloseExpiredOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public  $order;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        $this->order =  $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            switch ($this->order->state) {
                case 1:
                    $res =  OrderService::close($this->order->id, '订单支付超时,系统自动关闭订单'); //待支付超时自动关闭
                    break;
                case 2:
                    $res = OrderService::refund($this->order->id, $this->order->money, 0, '门店超时未接单，系统自动退款'); //待接单超时自动退款
                    break;
                case 3:
                    $res = OrderService::maked($this->order->id); //制作时间超时自动完成制作
                    break;
                case 4:
                    if ($this->order->scene == 1) {
                        //$res = OrderService::delivery($this->order->id); //待配送超时自动配送
                        return true;
                    } else {
                        $res = OrderService::complete($this->order->id); //店内订单配送超时自动完成
                    }
                    break;
                case 5:
                    $res = OrderService::complete($this->order->id); //配送中 自动完成
                    break;
                case 7:
                    // return OrderService::refund($this->order->id, $this->order->money, 0, '', '配送超时,系统自动退款'); //申请退款超时未处理自动退款
                    break;
                default:
                    return true;
            }
            echo "\n";
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}
