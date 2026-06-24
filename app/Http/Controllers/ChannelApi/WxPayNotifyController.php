<?php

namespace App\Http\Controllers\ChannelApi;

use App\Enums\PayEnum;
use App\Models\Wechat;
use App\Services\Pay\WechatPay;
use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Jobs\WxfahuoJob;
use App\Jobs\WxRefundJob;
use App\Models\Order\OrderIndex;
use App\Models\Order\PayLog;
use App\Models\PayTemplate;
use App\Services\OrderNotifyService;
use App\Services\Pay\AliPay;
use App\Services\SwooleJobService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Services\LuckyWheelService;
class WxPayNotifyController extends BaseController
{

    use ApiResponse;
    /**
     * 微信jsApi支付回调
     */
    public function jsPay($uniacid, $payTemprateId)
    {
        $app = WechatPay::Payment($uniacid, $payTemprateId);
        $server  =  $app->getServer();
        $server->handlePaid(function ($message) use ($uniacid, $payTemprateId) {
            if(!is_array($message)){
                $message=json_decode($message,true);
            }
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
                $order->payType=1;
                $order->save();
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
                if ($res) {
                    //增加大转盘次数
                    LuckyWheelService::check($order);
                    return true;
                }
            } catch (\Exception $e) {
                file_put_contents('jspay.log',$e->getMessage().PHP_EOL,FILE_APPEND);
                return false;
            }
        });
        return $server->serve();
    }


    /**
     * 微信jsApi支付回调
     */
    public function fubei($payTemprateId)
    {
        $input = Request()->all();
        //file_put_contents('fubei.log',json_encode($input).PHP_EOL,FILE_APPEND);
        $message  = json_decode($input['data'], true);
        $ext = json_decode($message['attach'], true);
        $message['out_trade_no'] = $ext['takeOutNo'];
        $userId = $ext['userId'] ?? 0;
        try {
            $payTemprate = PayTemplate::find($payTemprateId);
            $payLog = PayLog::where("orderSn", $ext['takeOutNo'])->first();
            if (empty($payLog)) {
                dispatch(new WxRefundJob($message, $payTemprate->uniacid, $payTemprateId, 'fubei'));
                return false;
            }
            $order = OrderIndex::where('orderSn', $payLog->orderSn)->unpaid()->first();
            if (empty($order)) {
                return false;
            }
            $order->payType=17;
            $order->save();
            $message['payChannel'] = $payTemprate->storeId > 0 ? 2 : 1;
            $message['transaction_id'] = $message['order_sn'];
            $message['trade_type'] = PayEnum::fubeiPayChannel($message['pay_type']);
            if ($order->type == 1) {
                $res =  OrderNotifyService::takeout($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 2) {
                $res = OrderNotifyService::storeValue($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 3) {
                $res  = OrderNotifyService::personPay($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 4) {
                $res = OrderNotifyService::inStore($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 5) {
                $res = OrderNotifyService::pointsMail($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 6) {
                $res = OrderNotifyService::couponPack($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 7) {
                $res = OrderNotifyService::tableReserve($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 8) {
                $res = OrderNotifyService::equityCard($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($res) {
                echo "success";
                return;
            }
            return false;
        } catch (\Exception $e) {
            file_put_contents('fubei.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }

    /**
     * 微信jsApi支付回调
     */
    public function suixingfu($payTemprateId)
    {
        $message = Request()->all();
        //file_put_contents('suixingfu.log',$payTemprateId.PHP_EOL,FILE_APPEND);
        //file_put_contents('suixingfu.log',json_encode($message).PHP_EOL,FILE_APPEND);
        $ext = json_decode($message['extend'], true);
        try {
            $userId = $ext['userId'];
            $message['transaction_id'] = $message['sxfUuid'];
            $message['trade_type'] = PayEnum::sxfPayChannel($message['payType']);
            $message['payer']['openid'] = $message['openid'];
            $message['out_trade_no'] = $message['ordNo'];
            $payTemprate = PayTemplate::find($payTemprateId);
            $message['payChannel'] = $payTemprate->storeId > 0 ? 2 : 1;
            $payLog = PayLog::where("paySn", $message['ordNo'])->first();
            if (empty($payLog)) {
                dispatch(new WxRefundJob($message, $payTemprate->uniacid, $payTemprateId, 'suixingfu'));
                return false;
            }
            $order = OrderIndex::where('orderSn', $payLog->orderSn)->unpaid()->first();
            if (empty($order)) {
                return false;
            }
            $order->payType=18;
            $order->save();
            if ($order->type == 1) {
                $res = OrderNotifyService::takeout($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 2) {
                $res = OrderNotifyService::storeValue($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 3) {
                $res = OrderNotifyService::personPay($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 4) {
                $res = OrderNotifyService::inStore($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 5) {
                $res = OrderNotifyService::pointsMail($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 6) {
                $res = OrderNotifyService::couponPack($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 7) {
                $res = OrderNotifyService::tableReserve($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 8) {
                $res = OrderNotifyService::equityCard($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($res) {
                echo json_encode([
                    'code' => 'success',
                    'msg' => '成功'
                ]);
            }
            return false;
        } catch (\Exception $e) {
            file_put_contents('suixingfu.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }

    public function aliPay($uniacid, $payTemprateId)
    {
        $app = AliPay::payment($uniacid, $payTemprateId, '');
        $message = $app->callback();
        //file_put_contents('aliPay.log',$payTemprateId.PHP_EOL,FILE_APPEND);
        //file_put_contents('aliPay.log',json_encode($message).PHP_EOL,FILE_APPEND);
        $message = json_decode($message, true);
        Log::error($message);
        try {
            if ($message['trade_status'] != 'TRADE_SUCCESS') {
                return $app->success();
            }
            $payLog = PayLog::where("paySn", $message['out_trade_no'])->first();
            if (empty($payLog)) {
                //dispatch(new WxRefundJob($message, $payTemprate->uniacid, $payTemprateId, 'suixingfu'));
                return false;
            }
            $order = OrderIndex::where('orderSn', $payLog->orderSn)->unpaid()->first();
            if (empty($order)) {
                return $app->success();
            }
            $order->payType=26;
            $order->save();
            $message['payChannel'] = 1;
            $message['trade_type'] = PayEnum::aliPayChannel('DISCOUNT');
            $message['transaction_id'] = $message['trade_no'];
            $message['payer']['openid'] = $message['buyer_id'];
            $attach = json_decode($message['attach'], true);
            if ($order->type == 1) {
                $res = OrderNotifyService::takeout($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 2) {
                $res = OrderNotifyService::storeValue($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 3) {
                $res = OrderNotifyService::personPay($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 4) {
                $res = OrderNotifyService::inStore($message, $payLog->orderSn, $payTemprateId, $attach['userId'] ?? 0);
            }
            if ($order->type == 5) {
                $res = OrderNotifyService::pointsMail($message, $payLog->orderSn, $payTemprateId, $attach['userId'] ?? 0);
            }
            if ($order->type == 6) {
                $res = OrderNotifyService::couponPack($message, $payLog->orderSn, $payTemprateId, $attach['userId'] ?? 0);
            }
            if ($order->type == 7) {
                $res = OrderNotifyService::tableReserve($message, $payLog->orderSn, $payTemprateId, $attach['userId'] ?? 0);
            }
            if ($order->type == 8) {
                $res = OrderNotifyService::equityCard($message, $payLog->orderSn, $payTemprateId, $attach['userId'] ?? 0);
            }
            if ($res) {
                return $app->success();
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function zhongyin($payTemprateId)
    {

        $input = file_get_contents('php://input');
        $message=urldecode($input);
        //file_put_contents('zhongyin.log',$payTemprateId.PHP_EOL,FILE_APPEND);
        //file_put_contents('zhongyin.log',json_encode($message).PHP_EOL,FILE_APPEND);
        try {
            $message=json_decode($message,true);
            $lock_key = 'zhongyin' . $message['merchantOrderNo'];
            $is_lock  = Cache::lock($lock_key, 1);
            if (!$is_lock) { // 获取锁权限
                // 防止死锁
                throw new BadRequestHttpException('正在处理');
            }
            $payTemprate = PayTemplate::find($payTemprateId);

            $payLog = PayLog::where("paySn", $message['merchantOrderNo'])->first();
            if (empty($payLog)) {
                dispatch(new WxRefundJob($message, $payLog['uniacid'], $payTemprateId, 'weixin'));
                return false;
            }
            $order = OrderIndex::where('orderSn', $payLog['orderSn'])->unpaid()->first();
            if (empty($order)) {
                return false;
            }
            $message['payChannel'] = $payTemprate->storeId > 0 ? 2 : 1;
            $message['trade_type'] = PayEnum::wexinPayChannel('JSAPI');
            Wechat::wxfahuoJob(['uniacid' => $order->uniacid, 'orderSn' => $order->orderSn, 'openid' => $message['userId'] , 'transaction_id' => $message['logNo']]);
            //dispatch(new WxfahuoJob(['uniacid' => $order->uniacid, 'orderSn' => $order->orderSn, 'openid' => $message['userId'] , 'transaction_id' => $message['logNo']]));

            $message['profit_sharing'] = 'N';
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
            if ($res) {
                echo 'success';
            }
        } catch (\Exception $e) {
            return false;
        }

    }


    public function HuiLaiMiPay($payTemprateId)
    {
        $message = Request()->all();
       // file_put_contents('huiLaiMiPay.log',$payTemprateId.PHP_EOL,FILE_APPEND);
        //file_put_contents('huiLaiMiPay.log',json_encode($message).PHP_EOL,FILE_APPEND);
        Log::error($message);

        try {

            if($message['resp_code']!=='000000'){
                echo "success";
                return ;
            }
            $message = $message['data'];
            $ext = json_decode($message,true);


            // if(!in_array($ext['bank_code'] , ["10000", "SUCCESS", "TRADE_SUCCESS"])){
            //     echo "success";
            //     return ;
            // }

            $lock_key = 'HuiLaiMiPay' . $ext['order_id'];
            $is_lock  = Cache::lock($lock_key, 1);
            if (!$is_lock) { // 获取锁权限
                // 防止死锁
                throw new BadRequestHttpException('正在处理');
            }

            $userId = '';

            $messages['transaction_id'] = $ext['out_trans_id'];
            $messages['trade_type'] = PayEnum::hlmPayChannel($ext['pay_type']);

            $messages['payer']['openid'] = $ext['open_id']??'';
            $messages['out_trade_no'] = $ext['order_id'];
            $messages['out_trans_id'] = $ext['party_order_id'];

            $payTemprate = PayTemplate::find($payTemprateId);
            $messages['payChannel'] = $payTemprate->storeId > 0 ? 2 : 1;
            $payLog = PayLog::where("paySn", $ext['order_id'])->first();

            if (empty($payLog)) {
                dispatch(new WxRefundJob($messages, $payTemprate->uniacid, $payTemprateId, 'HuiLaiMiPay'));
                return false;
            }
            $order = OrderIndex::where('orderSn', $payLog->orderSn)->unpaid()->first();

            if (empty($order)) {
                return false;
            }
            $order->payType=19;
            $order->save();
            if ($order->type == 1) {
                $res = OrderNotifyService::takeout($messages, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 2) {
                $res = OrderNotifyService::storeValue($messages, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 3) {
                $res = OrderNotifyService::personPay($messages, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 4) {
                $res = OrderNotifyService::inStore($messages, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 5) {
                $res = OrderNotifyService::pointsMail($messages, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 6) {
                $res = OrderNotifyService::couponPack($messages, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 7) {
                $res = OrderNotifyService::tableReserve($messages, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 8) {
                $res = OrderNotifyService::equityCard($messages, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($res) {
                optional($is_lock)->release();
                echo "success";
                return ;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        } finally {
            optional($is_lock)->release();
        }
    }


    /**
     * 拉卡拉
     * @param $payTemprateId
     * @return false|void
     */
    public function LaKaLaPay($payTemprateId)
    {
        $message = Request()->all();
        Log::error($message);

        try {
            $this->log($message,"lkl");
            if(!in_array($message['trade_state'] , ["SUCCESS"])){
                echo json_encode(["code"      => "SUCCESS", "message"   => "成功",]);
                return ;
            }
            $lock_key = 'LaKaLaPay' . $message['out_trade_no'];
            $is_lock  = Cache::lock($lock_key, 1);
            if (!$is_lock) { // 获取锁权限
                // 防止死锁
                throw new BadRequestHttpException('正在处理');
            }
            $userId = $message['user_id2']??'';
            $message['transaction_id'] = $message['acc_trade_no'];
            $message['trade_type'] = PayEnum::lklPayChannel($message['account_type']);
            $message['payer']['openid'] = $message['user_id2']??'';
            $message['out_trade_no'] = $message['out_trade_no'];
            $payTemprate = PayTemplate::find($payTemprateId);
            $message['payChannel'] = $payTemprate->storeId > 0 ? 2 : 1;
            $payLog = PayLog::where("paySn", $message['out_trade_no'])->first();
            if (empty($payLog)) {
                dispatch(new WxRefundJob($message, $payTemprate->uniacid, $payTemprateId, 'LaKaLaPay'));
                return false;
            }
            $order = OrderIndex::where('orderSn', $payLog->orderSn)->unpaid()->first();
            if (empty($order)) {
                return false;
            }
            $order->payType=20;
            $order->save();
            if ($order->type == 1) {
                $res = OrderNotifyService::takeout($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 2) {
                $res = OrderNotifyService::storeValue($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 3) {
                $res = OrderNotifyService::personPay($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 4) {
                $res = OrderNotifyService::inStore($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 5) {
                $res = OrderNotifyService::pointsMail($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 6) {
                $res = OrderNotifyService::couponPack($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 7) {
                $res = OrderNotifyService::tableReserve($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 8) {
                $res = OrderNotifyService::equityCard($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($res) {
                optional($is_lock)->release();
                echo json_encode(["code"      => "SUCCESS", "message"   => "成功",]);
                return ;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        } finally {
            optional($is_lock)->release();
        }
    }


    /**
     * 拉卡拉半屏回调
     * @param $payTemprateId
     * @return false|void
     */
    public function LaKaLaPayCashierDesktop($payTemprateId)
    {
        $message = Request()->all();
        Log::error($message);

        try {
            $this->log($message,"lkl");
            $lock_key = 'LaKaLaPay' . $message['out_order_no'];
            $is_lock  = Cache::lock($lock_key, 1);
            if (!$is_lock) { // 获取锁权限
                // 防止死锁
                throw new BadRequestHttpException('正在处理');
            }
            $userId = $message['order_trade_info']['user_id2']??'';
            $message['transaction_id'] = $message['order_trade_info']['trade_no']??"";
            $message['trade_type'] = PayEnum::lklPayChannel($message['order_trade_info']['pay_mode']);
            $message['payer']['openid'] = $message['user_id2']??'';
            $message['out_trade_no'] = $message['out_order_no'];
            $payTemprate = PayTemplate::find($payTemprateId);
            $message['payChannel'] = $payTemprate->storeId > 0 ? 2 : 1;
            $payLog = PayLog::where("paySn", $message['out_trade_no'])->first();
            if (empty($payLog)) {
                dispatch(new WxRefundJob($message, $payTemprate->uniacid, $payTemprateId, 'LaKaLaPay'));
                return false;
            }
            $order = OrderIndex::where('orderSn', $payLog->orderSn)->unpaid()->first();
            if (empty($order)) {
                return false;
            }
            if ($order->type == 1) {
                $res = OrderNotifyService::takeout($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 2) {
                $res = OrderNotifyService::storeValue($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 3) {
                $res = OrderNotifyService::personPay($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 4) {
                $res = OrderNotifyService::inStore($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 5) {
                $res = OrderNotifyService::pointsMail($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 6) {
                $res = OrderNotifyService::couponPack($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 7) {
                $res = OrderNotifyService::tableReserve($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 8) {
                $res = OrderNotifyService::equityCard($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($res) {
                optional($is_lock)->release();
                echo json_encode(["code"      => "SUCCESS", "message"   => "成功",]);
                return ;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        } finally {
            optional($is_lock)->release();
        }
    }

    /**
     * 自研半屏回调
     * @param $payTemprateId
     * @return false|void
     */
    public function CashierDesktop($payTemprateId)
    {
        $message = file_get_contents("php://input");
        $message = json_decode($message,1);
        //$message = Request()->all();
        Log::error($message);

        try {
            $this->log($message,"lkl");
            if(!in_array($message['orderStatus'] , ["TRADE_SUCCESS"])){
                echo json_encode(["code"      => "SUCCESS", "message"   => "成功",]);
                return ;
            }
            $lock_key = 'LaKaLaPay' . $message['orderNo'];
            $is_lock  = Cache::lock($lock_key, 1);
            if (!$is_lock) { // 获取锁权限
                // 防止死锁
                throw new BadRequestHttpException('正在处理');
            }
            $userId = $message['user_id2']??'';
            $message['transaction_id'] = $message['upOrderNo'];
            $message['trade_type'] = "微信|支付宝|银联";
            $message['payer']['openid'] = $message['user_id2']??'';
            $message['out_trade_no'] = $message['orderNo'];
            $message['transaction_id'] = $message['upOrderNo'];
            $payTemprate = PayTemplate::find($payTemprateId);
            $message['payChannel'] = $payTemprate->storeId > 0 ? 2 : 1;
            $payLog = PayLog::where("paySn", $message['orderNo'])->first();
            if (empty($payLog)) {
                dispatch(new WxRefundJob($message, $payTemprate->uniacid, $payTemprateId, 'LaKaLaPay'));
                return false;
            }
            $order = OrderIndex::where('orderSn', $payLog->orderSn)->unpaid()->first();
            if (empty($order)) {
                return false;
            }
            if ($order->type == 1) {
                $res = OrderNotifyService::takeout($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 2) {
                $res = OrderNotifyService::storeValue($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 3) {
                $res = OrderNotifyService::personPay($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 4) {
                $res = OrderNotifyService::inStore($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 5) {
                $res = OrderNotifyService::pointsMail($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 6) {
                $res = OrderNotifyService::couponPack($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 7) {
                $res = OrderNotifyService::tableReserve($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 8) {
                $res = OrderNotifyService::equityCard($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($res) {
                optional($is_lock)->release();
                echo json_encode(["code"      => "SUCCESS", "message"   => "成功",]);
                return ;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        } finally {
            optional($is_lock)->release();
        }
    }
    public function log($saveData,$name="hlm"){
        $target_dir = iconv("UTF-8", "GBK","../log/notify/{$name}/".date("Ym")."/");
        if(!file_exists($target_dir)){
            mkdir($target_dir,0777,true);
        }
        $name = $target_dir.date("Ymd").".log";
        $file = fopen($name,"a");
        fwrite($file , "\n".date("Y-m-d H:i:s"));
        fwrite($file , "\n".json_encode($saveData,JSON_UNESCAPED_UNICODE));
        fclose($file);
    }

    public function yidianfu($payTemprateId)
    {
        $message = file_get_contents('php://input');
        //file_put_contents('1.log',$message);

        try {
            $message = json_decode($message, true);
            // $lock_key = 'yidianfu' . $message['order_no'];
            // $is_lock  = Cache::lock($lock_key, 1);
            // if (!$is_lock) { // 获取锁权限
            //     // 防止死锁
            //     throw new BadRequestHttpException('正在处理');
            // }
            $userId = '';
            $payTemprate = PayTemplate::find($payTemprateId);
            $message['payChannel'] = $payTemprate->storeId > 0 ? 2 : 1;
            $message['transaction_id'] = $message['out_trade_no'];
            $message['trade_type'] = $message['trans_type'];
            $message['payer']['openid'] = $message['openid']??'';
            $message['out_trade_no'] = $message['order_no'];
            $payLog = PayLog::where("paySn", $message['order_no'])->first();
            $order = OrderIndex::where('orderSn', $payLog->orderSn)->unpaid()->first();
            if (empty($order)) {
                return false;
            }
            if ($order->type == 1) {
                $res = OrderNotifyService::takeout($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 2) {
                $res = OrderNotifyService::storeValue($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 3) {
                $res = OrderNotifyService::personPay($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 4) {
                $res = OrderNotifyService::inStore($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 5) {
                $res = OrderNotifyService::pointsMail($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 6) {
                $res = OrderNotifyService::couponPack($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 7) {
                $res = OrderNotifyService::tableReserve($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 8) {
                $res = OrderNotifyService::equityCard($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            echo "success";
        } catch (\Exception $e) {
            file_put_contents('WxPayNotify.log',$e->getMessage());
        }
    }
}
