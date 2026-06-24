<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as BaseController;
use App\Enums\PayEnum;
use App\Models\Admin\Apply;
use App\Models\AdminOrder;
use App\Models\SmsAccount;
use App\Models\SmsOrder;
use App\Services\Pay\AdminWechatPay;
use App\Services\SmsAccountService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\TryCatch;

class WxPayNotifyController extends BaseController
{

    /**
     * 微信jsApi支付回调
     */
    public function sms($uniacid)
    {
        $app = AdminWechatPay::Payment();
        $server  =  $app->getServer();
        Log::error('短信回调');
        $server->handlePaid(function ($message) use ($uniacid) {
            try {
                Log::error($message);
                Log::error($uniacid);
                $order = SmsOrder::where('outTradeNo', $message['out_trade_no'])->where('status', 1)->first();
                Log::error($order);
                if ($order) {
                    return true;
                }
                $attach = json_decode($message['attach'], true);
                $data = [
                    'status' => 1,
                    'uniacid' => $uniacid,
                    'source' => PayEnum::wexinPayChannel($message['trade_type']),
                    'userId' => $attach['userId'],
                    'outTradeNo' => $message['out_trade_no'],
                    'transaction_id' => $message['transaction_id'],
                    'money' => bcdiv($message['amount']['payer_total'], 100, 2),
                    'number' => $attach['number'],
                ];
                Log::error($data);
                $order = SmsOrder::create($data);
                Log::error($order);
                if (!$order) {
                    return false;
                }
                SmsAccountService::topUp($order->uniacid, $attach['number'], 0, "充值{$attach['number']}条短信", $order->outTradeNo);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        });
        return $server->serve();
    }

    public function muster()
    {
        $app = AdminWechatPay::Payment();
        $server  =  $app->getServer();
        $server->handlePaid(function ($message) {
            try {
                Log::error($message);
                $adminOrder = AdminOrder::where('outTradeNo', $message['out_trade_no'])->where('state', 0)->first();
                if (empty($adminOrder)) {
                    return false;
                }
                $adminOrder->transaction_id = $message['transaction_id'];
                $adminOrder->state = 1;
                $adminOrder->applyId = intval($adminOrder->attach['applyId']);
                $adminOrder->payType = PayEnum::wexinPayChannel($message['trade_type']);
                if ($adminOrder->save()) {
                    return $adminOrder->createApply();
                }
                return false;
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        });
        return $server->serve();
    }


    public function xftc()
    {
        $app = AdminWechatPay::Payment();
        $server  =  $app->getServer();
        $server->handlePaid(function ($message) {
            try {
                Log::error($message);
                $adminOrder = AdminOrder::where('outTradeNo', $message['out_trade_no'])->where('state', 0)->first();
                if (empty($adminOrder)) {
                    return false;
                }
                $adminOrder->transaction_id = $message['transaction_id'];
                $adminOrder->state = 1;
                $adminOrder->payType = PayEnum::wexinPayChannel($message['trade_type']);
                if ($adminOrder->save()) {
                    $apply = Apply::find($adminOrder->aplyId);
                    $apply->musterId = $adminOrder->goodsId;
                    $isDirty = $apply->isDirty('musterId');
                    $apply->endTime = $adminOrder->day * 86400  + strtotime($apply->startTime);
                    $apply->save();
                    if ($isDirty) {
                        return  $apply->refreshPlugs();
                    } else {
                        return  $apply->updatePlugs();
                    }
                }
                return false;
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        });
        return $server->serve();
    }
}
