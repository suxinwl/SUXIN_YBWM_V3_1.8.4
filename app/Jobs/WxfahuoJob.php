<?php

namespace App\Jobs;

use App\Models\Order\OrderIndex;
use App\Services\OpenWechat\ChannelOpenWechat;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Mail;

class WxfahuoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $uniacid;
    public $orderSn;
    public $transaction_id;
    public $openid;
    public $tries = 5;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($message)
    {
        Log::error("-----message----");
        Log::error($message);
        $this->uniacid = $message['uniacid'];
        $this->orderSn = $message['orderSn'];
        $this->transaction_id = $message['transaction_id'];
        $this->openid = $message['openid'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $uniacid = $this->uniacid;
            $config = ChannelOpenWechat::getConfig($uniacid, 'mini');
            $app = ChannelOpenWechat::miniProgram($uniacid);
            $res = $app->httpPostJson('wxa/sec/order/is_trade_managed', ['appid' => $config->authorizer_appid]);
            if ($res['errcode'] != 0 || $res['is_trade_managed'] == false) {
                return false;
            }
            $data = [
                'order_key' => [
                    'order_number_type' => 2,
                    'transaction_id' => $this->transaction_id,
                ],
                'logistics_type' => 4,
                'delivery_mode' => 1,
                'shipping_list' => [
                    [
                        'item_desc' => '订单商品已发货,请确认收货',
                    ]
                ],
                'upload_time' => date("c", time()),
                'payer' => [
                    'openid' => $this->openid
                ]
            ];
            Log::error($data);
            $res = $app->httpPostJson('wxa/sec/order/upload_shipping_info', $data);
            Log::error($res);
            if ($res['errcode'] != 0) {
                return false;
            }
        } catch (\Exception $e) {
            file_put_contents('WxfahuoJob.log',$e->getMessage().PHP_EOL,FILE_APPEND);
        }
    }
}
