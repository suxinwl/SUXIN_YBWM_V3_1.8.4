<?php

namespace App\Console\Commands\Order;

use App\Enums\PayEnum;
use App\Jobs\WxfahuoJob;
use App\Jobs\WxRefundJob;
use App\Models\Order\PayLog;
use App\Services\OrderNotifyService;
use App\Services\StaticService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\InStore\Order\Order;

class QueryOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:queryorder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '订单查询';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $begintime = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
        $endtime = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1);


        $message = '{
            "sp_mchid": "1640513915",
            "sub_mchid": "1671264173",
            "sp_appid": "wx3e42381088d566c8",
            "sub_appid": "wx34783b7ac64b2a54",
            "out_trade_no": "20240627201221716925",
            "transaction_id": "4200002166202406270177220462",
            "trade_type": "JSAPI",
            "trade_state": "SUCCESS",
            "trade_state_desc": "\u652f\u4ed8\u6210\u529f",
            "bank_type": "OTHERS",
            "attach": "{\"takeOutNo\":\"20240627201219524439\",\"userId\":63330,\"profit_sharing\":0}",
            "success_time": "2024-06-27T20:12:28+08:00",
            "payer": {
                "sp_openid": "oF7KL5lwbR1NzwxRhASDGxQ8EHVM",
                "sub_openid": "ouFTK68Sxr0YVAzJWwwM2jCNMznQ"
            },
            "amount": {
                "total": 6700,
                "payer_total": 6700,
                "currency": "CNY",
                "payer_currency": "CNY"
            }
        }';
        if (!is_array($message)) {
            $message = json_decode($message, true);
        }
        $message['out_trade_no']='';
        $payLog = PayLog::where("paySn", $message['out_trade_no'])->first();
        $uniacid = $payLog['uniacid'];
        $payConfig = PayConfig::where('uniacid', $payLog['uniacid'])
            ->where('payType', 'weixin')->where('state', '1')
            ->first();
        $payTemprateId = $payConfig->templateId;
        $payTemprate = PayTemplate::find($payTemprateId);

        if (empty($payLog)) {
            dispatch(new WxRefundJob($message, $uniacid, $payTemprateId, 'weixin'));
            return false;
        }
        $order = OrderIndex::where('orderSn', $payLog['orderSn'])->unpaid()->first();
        if (empty($order)) {
            return false;
        }
        $message['payChannel'] = $payTemprate->storeId > 0 ? 2 : 1;
        $message['trade_type'] = PayEnum::wexinPayChannel($message['trade_type']);
        dispatch(new WxfahuoJob(['uniacid' => $order->uniacid, 'orderSn' => $order->orderSn, 'openid' => $message['payer']['openid'] ?? $message['payer']['sub_openid'], 'transaction_id' => $message['transaction_id']]));
        $attach = json_decode($message['attach'], true);
        $message['profit_sharing'] = $attach['profit_sharing'];
        if ($order->type == 1) {
            $res = OrderNotifyService::takeout($message, $payLog['orderSn'], $payTemprateId);
        }
        if ($order->type == 2) {
            $res = OrderNotifyService::storeValue($message, $payLog['orderSn'], $payTemprateId);
        }
        if ($order->type == 3) {
            $res = OrderNotifyService::personPay($message, $payLog['orderSn'], $payTemprateId);
        }
        if ($order->type == 4) {
            $res = OrderNotifyService::inStore($message, $payLog['orderSn'], $payTemprateId, $attach['userId'] ?? 0);
        }
        if ($order->type == 5) {
            $res = OrderNotifyService::pointsMail($message, $payLog['orderSn'], $payTemprateId, $attach['userId'] ?? 0);
        }
        if ($order->type == 6) {
            $res = OrderNotifyService::couponPack($message, $payLog['orderSn'], $payTemprateId, $attach['userId'] ?? 0);
        }
        if ($order->type == 7) {
            $res = OrderNotifyService::tableReserve($message, $payLog['orderSn'], $payTemprateId, $attach['userId'] ?? 0);
        }
        if ($order->type == 8) {
            $res = OrderNotifyService::equityCard($message, $payLog['orderSn'], $payTemprateId, $attach['userId'] ?? 0);
        }

    }
}
