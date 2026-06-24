<?php

namespace App\Services;

use App\Enums\PayEnum;
use App\Models\InStore\Order\Order;
use App\Models\Member\MemberQrCode;
use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Models\Store\AccountLog;
use App\Services\Pay\AliPay;
use App\Services\Pay\FubeiPay;
use App\Services\Pay\Suixingfu;
use App\Services\Pay\WechatPay;
use App\Services\Pay\HuiLaiMiPay;
use App\Services\Pay\LaKaLaPay;
use App\Traits\ResourceTrait;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use Illuminate\Support\Facades\Log;
use App\Models\Order\OrderIndex;
use App\Services\MemberAccountService;
use App\Services\Pay\YiDianFuPay;
use App\Models\Store;
class PayService
{
    public static function pay($order, $uniacid, $payId, $appType)
    {
        if (empty($order['orderSn'])) {
            throw new BadRequestException('订单已取消或者已支付');
        }
        $orderIndex = OrderIndex::unpaid()->where('orderSn', $order['orderSn'])->first();
        if (empty($orderIndex)) {
            throw new BadRequestException('订单已取消或者已支付');
        }
        $payConfig = PayConfig::where('uniacid', $uniacid)->find($payId);
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付方式不存在');
        }
        if ($payConfig->state == 0) {
            throw new BadRequestHttpException('支付方式已关闭');
        }

        if ($payConfig->payType == 'balance') {
            if ($order['balance'] == 0) {
                throw new BadRequestHttpException('账户余额不足');
            }
            $data = MemberAccountService::pay($order['orderSn'], $payConfig->templateId, $order['userId']);
        } else {
            if ($payConfig->payTemplate->type == 3) {
                $fubei = new FubeiPay();
                $data =  $fubei->jsApiPay($order, $uniacid, $payId, $appType, $payConfig->templateId);
            } elseif ($payConfig->payTemplate->type == 4) {
                $fubei = new Suixingfu();
                $data =  $fubei->jsApiPay($order, $uniacid, $payId, $appType, $payConfig->templateId);
            }elseif ($payConfig->payTemplate->type == 5) {//回来米支付渠道
                $fubei = new HuiLaiMiPay();
                $data =  $fubei->jsApiPay($order, $uniacid, $payId, $appType, $payConfig->templateId);
            }elseif ($payConfig->payTemplate->type == 6) {//回来米支付渠道
                $fubei = new LaKaLaPay();
                $data =  $fubei->jsApiPay($order, $uniacid, $payId, $appType, $payConfig->templateId);
            } elseif ($payConfig->payTemplate->type == 7) {
                $fubei = new YiDianFuPay();
                $data =  $fubei->jsApiPay($order, $uniacid, $payId, $appType, $payConfig->templateId);
            }elseif ($payConfig->payTemplate->channel == 'weixin' ||$payConfig->payTemplate->channel == 'zhongyin'&& in_array($payConfig->payTemplate->type, [1, 2])) {

                $data = WechatPay::jsApiPay($order, $uniacid, $payId, $appType);
            }elseif ($payConfig->payTemplate->channel == 'alipay'&&$payConfig->payTemplate->data['model'] == '4') {
                $fubei = new Suixingfu();
                $data =  $fubei->jsApiPay($order, $uniacid, $payId, $appType, $payConfig->templateId);
            }elseif ($payConfig->payTemplate->channel == 'alipay'&&$payConfig->payTemplate->data['model'] == '5') {
                $fubei = new HuiLaiMiPay();
                $data =  $fubei->jsApiPay($order, $uniacid, $payId, $appType, $payConfig->templateId);
            }elseif ($payConfig->payTemplate->channel == 'alipay'&&$payConfig->payTemplate->data['model'] == '6') {
                $fubei = new LaKaLaPay();
                $data =  $fubei->jsApiPay($order, $uniacid, $payId, $appType, $payConfig->templateId);
            } elseif ($payConfig->payTemplate->channel == 'alipay') {
                $fubei = new AliPay();
                $data =  $fubei->jsApiPay($order, $uniacid, $payId, $appType);
            }
        }
        return $data;
    }

    public static function withdrawal($order, $uniacid, $storeId)
    {
        $app = WechatPay::withdrawalApp($uniacid, $storeId);


        $platformCertificateFilePath=$app->getConfig()['platform_certs'][0];

        $platformCertificateSerial = PemUtil::parseCertificateSerialNo(file_get_contents($platformCertificateFilePath));
        $order = [
            'headers' => ['Wechatpay-Serial' => $platformCertificateSerial],
            "json" => [
                "appid" => $order['app_id'],
                "out_batch_no" => getTakeOutNo(),
                "batch_name" => "打款",
                "batch_remark" => "打款",
                "total_amount" => intval(bcmul($order['amount'], 100, 0)),
                "total_num" => 1,
                "transfer_detail_list" => [[
                    "out_detail_no" => getTakeOutNo(),
                    "transfer_amount" => intval(bcmul($order['amount'], 100, 0)),
                    "transfer_remark" => "打款",
                    "openid" => $order['openid'],
                    "user_name" =>$order['userName']
                    //"user_name" => Rsa::encrypt($order['userName'], file_get_contents($app->getConfig()['platform_certs'][0]))
                ]]
            ]
        ];
        $response = $app->getClient()->postJson('v3/transfer/batches', $order);
        if ($response->isFailed()) {
            throw  new BadRequestException($response->getContent(false));
        }

        return true;
    }

    public static  function refund($order, $uniacid, $payId)
    {
        if ($payId == !0) {
            $payConfig = PayTemplate::where('uniacid', $uniacid)->find($payId);
            if (empty($payConfig)) {
                throw new BadRequestHttpException('支付方式不存在');
            }
        } else {
            $data = MemberAccountService::refund($order);
        }
        if ($payConfig->type == 3) {
            $fubei = new FubeiPay();
            $data =  $fubei->refund($order, $uniacid, $payId);
        } elseif ($payConfig->type == 4) {
            $fubei = new Suixingfu();
            $data =  $fubei->refund($order, $uniacid, $payId);
        } elseif ($payConfig->channel == 'weixin' && in_array($payConfig->type, [1, 2])) {
            $data = WechatPay::refund($order, $uniacid, $payId);
        } elseif ($payConfig->channel == 'alipay' &&  in_array($payConfig->type, [1, 2])) {
            $fubei = new AliPay();
            $data =  $fubei->refund($order, $uniacid, $payId);
        }elseif ($payConfig->type == 7) {
            $fubei = new YiDianFuPay();
            $data =  $fubei->refund($order, $uniacid, $payId);
        }else{
            return true;
        }
        return $data;
    }

    public static function micropay($order)
    {
        try {
            Log::error("auth_code:" . $order['auth_code']);
            $fix = substr($order['auth_code'], 0, 2);
            $auth_code_arr = [10, 11, 12, 13, 14, 15, 22,25, 26, 27, 28, 29, 30,66, 68,88,98,99];
            if (!in_array($fix, $auth_code_arr)) {
                $order['auth_code'] = strrev($order['auth_code']);
            }
//            $orderInfo = Order::where('storeId',$order['storeId'])
//                ->where('orderSn', $order['takeOutNo'])
//                ->where('isPay', 0)
//                ->first();
//            if(empty($orderInfo)){
//                throw new BadRequestHttpException('订单状态异常,请重新发起支付');
//            }

            Log::error("auth_code1:" . $order['auth_code']);
            $payConfig = PayConfig::where('uniacid', $order['uniacid'])->where('payType', 'zhongyin')->where('state', 1)->first();
            if($payConfig){
                $payType='zhongyin';
            }else{
                $payType = self::authCodeForm($order['auth_code']);
            }
            $store = Store::where('uniacid', $order['uniacid'])->where('id', $order['storeId'])->first();
            $payChange=$store->payChange;
            $payConfig = PayConfig::where('uniacid', $order['uniacid'])
                ->where('state', 1)
                ->when($order['orderType'] != 2, function ($q) use ($order, $payType) {
                    if ($order['storePay'] == 0) {
                        return $q->where('storeId', 0)->where('payType', $payType);
                    } else {
                        return $q->where('storeId', $order['storeId'])->where('payType', $payType);
                    }
                })
                ->when($order['orderType'] == 2, function ($q) use ($payType, $order,$payChange) {
                    return $q->where('payType', '!=', 'balance')
                        ->where('payType', $payType)->when($payChange == 1, function ($q) use ($order) {
                            return $q->where('storeId', $order['storeId']);
                        });
                })
                ->when($order['amount'] == 0, function ($q) {
                    return $q->where('payType', 'balance');
                })
                ->first();
            if (empty($payConfig)) {
                throw new BadRequestHttpException('暂不支持该支付方式');
            }
            if ($payConfig->payType == 'balance') {
                $message = ['trade_type' => 0];
                $message['userId'] = MemberAccountService::micropay($order);
                MemberQrCode::where('qrcode', $order['auth_code'])->delete();
            } else {
                if ($payConfig->payTemplate->channel == 'weixin'&& in_array($payConfig->payTemplate->type, [1, 2])) {
                    $message = WechatPay::micropay($order, $payConfig->id);
                    $message['payChannel'] = $payConfig->storeId > 0 ? 2 : 1;
                    $message['trade_type'] = PayEnum::wexinPayChannel($message['trade_type']);
                    $message['profit_sharing'] = $payConfig->data['wxfz']['profitsharingSwitch'] == 1 ? 1 : 0;
                } elseif ($payConfig->payTemplate->channel == 'alipay' && in_array($payConfig->payTemplate->type, [1, 2])) {
                    $message = AliPay::micropay($order, $payConfig->id);
                    $message['payChannel'] = $payConfig->storeId > 0 ? 2 : 1;
                    $message['transaction_id'] = $message['trade_no'];
                    $message['trade_type'] = PayEnum::ALIPAY_PAY;
                    $message['payer']['openid'] = $message['buyer_user_id'];
                } elseif ($payConfig->payTemplate->type == 3) {
                    $fubei = new FubeiPay();
                    $message =  $fubei->micropay($order, $payConfig->id);
                    $message['payChannel'] = $payConfig->storeId > 0 ? 2 : 1;
                    $message['transaction_id'] = $message['order_sn'];
                    $message['trade_type'] = PayEnum::fubeiPayChannel($message['pay_type']);
                } elseif ($payConfig->payTemplate->type == 4) {
                    $fubei = new Suixingfu();
                    $message =  $fubei->micropay($order, $payConfig->id);
                    $message['transaction_id'] = $message['sxfUuid'];
                    $message['trade_type'] = PayEnum::sxfPayChannel($message['payType']);
                    $message['payer']['openid'] = $message['openid'];
                    $message['payChannel'] = $payConfig->storeId > 0 ? 2 : 1;
                }elseif ($payConfig->payTemplate->type == 5) {
                    $fubei = new HuiLaiMiPay();
                    $message =  $fubei->micropay($order, $payConfig->id);
                    $message['transaction_id'] = $message['out_trans_id'];
                    $message['trade_type'] = PayEnum::hlmPayChannel($message['pay_type']);
                    $message['payer']['openid'] = $message['openid']??'';
                    $message['payChannel'] = $payConfig->storeId > 0 ? 2 : 1;
                }elseif ($payConfig->payTemplate->type == 6) {
                    $fubei = new LaKaLaPay();
                    $message =  $fubei->micropay($order, $payConfig->id);
                    $message['transaction_id'] = $message['out_trans_id'];
                    $message['trade_type'] = PayEnum::hlmPayChannel($message['pay_type']);
                    $message['payer']['openid'] = $message['openid']??'';
                    $message['payChannel'] = $payConfig->storeId > 0 ? 2 : 1;
                }
                $message['payTempId'] = $payConfig->templateId;
            }
            return $message;
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    public static function authCodeForm($authCode)
    {
        $fix = substr($authCode, 0, 2);
        if ($fix >= 11 && $fix <= 15) {
            return 'weixin';
        }
        if ($fix >= 25 && $fix <= 30) {
            return 'alipay';
        }
        if ($fix == 98) {
            return 'balance';
        }
        if ($fix == 99||$fix == 68||$fix == 66||$fix == 88||$fix == 22) {
            return 'zhongyin';
        }
        throw new BadRequestHttpException('付款码错误');
    }
}
