<?php

namespace App\Console\Commands\Order;

use App\Enums\PayEnum;
use App\Jobs\WxfahuoJob;
use App\Jobs\WxRefundJob;
use App\Models\Admin\Apply;
use App\Models\Order\OrderIndex;
use App\Models\Order\PayLog;
use App\Models\StatisticsDay;
use App\Models\Store;
use App\Models\StoredValue;
use App\Models\Tables\Table;
use App\Services\BillService;
use App\Services\InStoreOrderService;
use App\Services\OrderNotifyService;
use App\Services\Pay\WechatPay;
use App\Services\StaticService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PayConfig;
use App\Models\PayTemplate;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
class OrderQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:query';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '扫码点餐订单补偿机制';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $order = DB::table('instore_order')->where('state', 1)
            ->orderBy('id', 'desc')->first();
        if($order){
           $uniacid=$order->uniacid;
           $storeId=$order->storeId;
           $payType='weixin';
            $payConfig = PayConfig::where('uniacid', $uniacid)
                ->where('state', 1)
                ->when($order['orderType'] != 2, function ($q) use ($order, $payType) {
                    if ($order['storePay'] == 0) {
                        return $q->where('storeId', 0)->where('payType', $payType);
                    } else {
                        return $q->where('storeId', $order->storeId)->where('payType', $payType);
                    }
                })
                ->when($order['orderType'] == 2, function ($q) use ($payType, $order) {
                    return $q->where('payType', '!=', 'balance')
                        ->where('payType', $payType)->when($order->isolate == 1, function ($q) use ($order) {
                            return $q->where('storeId', $order->storeId);
                        })->when($order['isolate'] == 0, function ($q) use ($order) {
                            return $q->where('storeId', 0);
                        });
                })
                ->when($order['amount'] == 0, function ($q) {
                    return $q->where('payType', 'balance');
                })
                ->first();
            if (empty($payConfig)) {
                throw new BadRequestException('微信配置错误');
            }
            $payTemprateId=$payConfig->id;
            $app = WechatPay::v2Payment($uniacid, $payTemprateId);

            $queryRes = $app->order->queryByOutTradeNumber($order->orderSn);
            $message=json_decode($queryRes,true);
            if($message['return_code']=='SUCCESS'&&$message['result_code']=='SUCCESS'){
                //file_put_contents('jsPaybucang.log',$payTemprateId.PHP_EOL,FILE_APPEND);
                //file_put_contents('jsPaybucang.log',$queryRes.PHP_EOL,FILE_APPEND);
                $payTemprate = PayTemplate::find($payTemprateId);
                try {
                    $payLog = PayLog::where("paySn", $message['out_trade_no'])->first();
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
                    $attach = json_decode($message['attach'], true);
                    $message['profit_sharing'] = $attach['profit_sharing'];
                    if ($order->type == 1) {
                        $res =  OrderNotifyService::takeout($message, $payLog['orderSn'], $payTemprateId);
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
                    dispatch(new WxfahuoJob(['uniacid' => $order->uniacid, 'orderSn' => $order->orderSn, 'openid' => $message['payer']['openid'] ?? $message['payer']['sub_openid'], 'transaction_id' => $message['transaction_id']]));
                    echo 'success';
                } catch (\Exception $e) {
                    file_put_contents('jspay.log',$e->getMessage().PHP_EOL,FILE_APPEND);
                    return false;
                }
            }
        }

    }
}
