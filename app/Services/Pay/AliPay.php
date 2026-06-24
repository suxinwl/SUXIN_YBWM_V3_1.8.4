<?php

namespace App\Services\Pay;

use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Models\RefundOrder;
use App\Services\BaseService;
use App\Services\ConfigService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Yansongda\Pay\Pay;

class AliPay extends BaseService
{
    public static function config($uniacid, $payConfigId, $notify_url = '')
    {
        $payTemplate = PayTemplate::where('uniacid', $uniacid)
            ->where('channel', 'alipay')
            ->where('id', $payConfigId)
            ->first();
        if (empty($payTemplate)) {
            throw new BadRequestException('当前支付模板不存在');
        }
        return $payTemplate->payConfig($notify_url);
    }

    public static function saveCert($data, $key = 'cert_path', $uniacid = 0)
    {
        if (empty($key)) {
            throw new BadRequestException('请配置支付宝证书');
        }
        $path =  app_path() . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . 'alipay' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . $uniacid;
        $file = $path . DIRECTORY_SEPARATOR . "{$key}.crt";
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        if (file_put_contents($file, $data)) {
            return $file;
        }
        return false;
    }

    public static function payment($uniacid, $payConfigId, $notify_url = '')
    {
        return Pay::alipay(array_merge(self::config($uniacid, $payConfigId, $notify_url), ['_force' => true]));
    }

    public static function jsApiPay($order, $uniacid, $payConfigId)
    {
        $payConfig = PayConfig::where('uniacid', $uniacid)->where('payType', 'alipay')->where('id', $payConfigId)->first();
        if (empty($payConfig)) {
            throw new BadRequestException('支付宝支付配置错误');
        }
        $notify_url = Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/aliPay/$uniacid/$payConfig->templateId";
        $app = self::payment($uniacid, $payConfig->templateId, $notify_url);
        $params = [
            'out_trade_no' => $order['takeOutNo'],
            'total_amount' => $order['amount'],
            'subject' => $order['desc'],
            'buyer_id' => $order['openid'],
        ];
        $result = $app->mini($params);
        if ($result['code'] != '10000') {
            throw new BadRequestException($result['sub_msg']);
        }
        return $result;
    }

    public static function wapPay($order, $uniacid, $payConfigId)
    {
        $payConfig = PayConfig::where('uniacid', $uniacid)->where('payType', 'alipay')->where('id', $payConfigId)->first();
        if (empty($payConfig)) {
            throw new BadRequestException('支付宝支付配置错误');
        }
        $notify_url = Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/aliPay/$uniacid/$payConfig->templateId";
        $app = self::payment($uniacid, $payConfig->templateId, $notify_url);
        $result = $app->wap([
            'out_trade_no' => $order['takeOutNo'],
            'total_amount' => $order['amount'],
            'subject' => $order['desc'],
            'buyer_id' => $order['openid'],
        ]);
        if ($result['code'] != '10000') {
            throw new BadRequestException($result['sub_msg']);
        }
        return $result;
    }

    public static function micropay($order, $payId)
    {
        $payConfig = PayConfig::where('uniacid', $order['uniacid'])
            ->where('payType', 'alipay')
            ->where('id', $payId)->where('state', 1)
            ->first();
        if (empty($payConfig)) {
            throw new BadRequestException('支付宝支付配置错误');
        }
        $app = self::payment($order['uniacid'], $payConfig->templateId);
        $res = $app->pos([
            'out_trade_no' => $order['takeOutNo'],
            'total_amount' => $order['amount'],
            "auth_code" => $order['auth_code'],
            'subject' => $order['desc'],
        ]);
        if (isset($res['sub_msg'])) {
            throw new BadRequestException($res['sub_msg']);
        } else {
            $state = 0;
            while ($state < 30) {
                $queryRes = $app->find(["out_trade_no" => $order['takeOutNo']]);
                if ($queryRes['trade_status'] == "TRADE_SUCCESS") {
                    $state = 99;
                    return $queryRes;
                } elseif ($queryRes['trade_status'] == 'TRADE_CLOSED' || $queryRes['trade_status'] == 'TRADE_FINISHED') {
                    //$app->reverse->byOutTradeNumber($order['takeOutNo']);
                    throw new BadRequestException('用户已取消支付');
                }
                $state++;
                sleep(1);
            }
            throw new BadRequestException('支付超时');
        }
    }

    public static function refund($order, $uniacid, $payId)
    {
        DB::beginTransaction();
        $payTemplate = PayTemplate::where('uniacid', $uniacid)->where('id', $payId)->first();
        if (empty($payTemplate)) {
            throw new BadRequestException('支付宝支付配置错误');
        }
        $app = self::payment($uniacid, $payId);
        $result = Pay::alipay()->refund([
            'trade_no' => $order['transaction_id'],
            'refund_amount' => $order['refund_amount'],
        ]);
        if ($result['code'] != '10000') {
            throw  new BadRequestException($result['msg']);
        }
        RefundOrder::create([
            'takeOutNo' => $order['takeOutNo'],
            'refundNo' => $result['out_refund_no'],
            'state' => 1,
            'data' => $result
        ]);
        DB::commit();
        return true;
    }
}
