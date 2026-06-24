<?php

namespace App\Jobs;

use App\Models\Order\OrderIndex;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\Pay\FubeiPay;
use App\Services\Pay\Suixingfu;
use App\Services\Pay\WechatPay;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Mail;

class WxRefundJob implements ShouldQueue
{
    public $message;
    public $type;
    public $uniacid;
    public $payTemprateId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($message, $uniacid, $payTemprateId, $type)
    {
        Log::error("-----message----");
        Log::error($message);
        Log::error($type);
        $this->message = $message;
        $this->type = $type;
        $this->payTemprateId = $payTemprateId;
        $this->uniacid = $uniacid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return true;
        try {
            $message = $this->message;
            switch ($this->type) {
                case 'weixin':
                    $order = [
                        'takeOutNo' => $message['out_trade_no'],
                        'total_amount' => bcdiv($message['amount']['payer_total'], 100, 2),
                        'refund_amount' => bcdiv($message['amount']['payer_total'], 100, 2),
                    ];
                    WechatPay::refund($order, $this->uniacid, $this->payTemprateId);
                    break;
                case 'ali':
                    break;
                case 'fubei':
                    $order = [
                        'transaction_id' => $message['order_sn'],
                        'refund_amount' => $message['total_amount']
                    ];
                    $fubei = new FubeiPay();
                    $fubei->refund($order, $this->uniacid, $this->payTemprateId);
                    break;
                case 'suixingfu':
                    $order['transaction_id'] = $message['sxfUuid'];
                    $order['refund_amount'] = $message['amt'];
                    $model = new Suixingfu();
                    $model->refund($order, $this->uniacid, $this->payTemprateId);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('payRefund');
            Log::error($e->getMessage());
        }
    }
}
