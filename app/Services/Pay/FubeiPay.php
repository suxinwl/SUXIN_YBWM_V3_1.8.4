<?php

namespace App\Services\Pay;

use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Models\RefundOrder;
use App\Services\BaseService;
use App\Services\ConfigService;
use EasyWeChat\Payment\Notify\Refunded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Swoole\FastCGI\HttpRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FubeiPay extends BaseService
{
    /**
     * 请求网关
     * @var string
     */
    private $gateway = 'https://shq-api.51fubei.com/gateway/agent';
    /**
     * 密钥
     * @var string
     */

    public $config = [
        'vendor_sn' => '',
        "secret" => '',
        "agentId" => ""
    ];
    public function __construct()
    {
        $this->config = [
            'vendor_sn' => env('FB_VENDOR_SN', '2022062015552336377a'),
            "secret" => env('FB_SECRET', '07efa97e719eb1d175a8d8f2ca3fdf67'),
        ];
        $config = ConfigService::getSystemSet('payConfig');
        if ($config->fbstate == 1) {
            $this->config = [
                'vendor_sn' => $config->vendor_sn,
                "secret" => $config->secret,
            ];
        }
    }

    public function payConfig($config, $uniacid, $appType = 'mini')
    {
        if ($res = Cache::get('fubeiConfig:' . $config['fb_store_id'], false) == false) {
            $bizContent = [
                'merchant_id' => $config['fb_store_id'],
                'store_id' => $config['fb_shop_id'],
                'sub_appid' => WechatPay::getAppId($uniacid),
                'account_type' => $appType == 'mini' ? 00 : 01,
                'jsapi_path' => "https://" . Request()->server('HTTP_HOST') . '/'
            ];
            $res = $this->postJson('fbpay.order.wxconfig', $bizContent);
            if ($res['result_code'] != 200) {
                throw new BadRequestHttpException($res['result_message']);
            }
            if ($res['data']['sub_appid_code'] != 1) {
                throw new BadRequestHttpException($res['data']['sub_appid_msg']);
            }
            if ($res['data']['jsapi_code'] != 1) {
                throw new BadRequestHttpException($res['data']['jsapi_msg']);
            }
            Cache::add('fubeiConfig:' . $uniacid, $res, 3600 * 24 * 30);
        }
        return $res;
    }

    public function jsApiPay($order, $uniacid, $payId, $appType = 'mini', $payTempId = 0)
    {
        $payConfig = PayConfig::where('uniacid', $uniacid)->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }
        $config = $payConfig->payTemplate->data;
        $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/fubei/$payTempId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/fubei/$payTempId";
        $bizContent = [
            'merchant_id' => $config['fb_store_id'],
            'merchant_order_sn' => $order['takeOutNo'],
            'pay_type' => $payConfig->payType == 'weixin' ? "wxpay" : "alipay",
            'total_amount' => $order['amount'],
            'user_id' => $order['openid'],
            'store_id' => $config['fb_shop_id'],
            'attach' => $order['attach'] ?: '',
            'notify_url' => $notify_url
        ];
        if ($payConfig->payType == 'weixin') {
            $this->payConfig($config, $uniacid, $appType);
            $bizContent['sub_appid'] = WechatPay::getAppId($uniacid);
        }
        $res =  $this->postJson('fbpay.order.create', $bizContent);
        if ($res['result_code'] != 200) {
            throw new BadRequestHttpException($res['result_message']);
        }
        return $payConfig->payType == 'weixin' ? $res['data']['sign_package'] : ['trade_no' => $res['data']['prepay_id']];
    }



    public function micropay($order, $payId)
    {
        $payConfig = PayConfig::where('uniacid', $order['uniacid'])->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }
        $config = $payConfig->payTemplate->data;
        $bizContent = [
            'merchant_id' => $config['fb_store_id'],
            'merchant_order_sn' => $order['takeOutNo'],
            'total_amount' => $order['amount'],
            'auth_code' => $order['auth_code'],
            'store_id' => $config['fb_shop_id']
        ];
        $res =  $this->postJson('fbpay.order.pay', $bizContent);
        if ($res['result_code'] != 200) {
            throw new BadRequestHttpException($res['result_message']);
        }
        if ($res['data']['order_status'] == "USERPAYING") {
            $state = 0;
            while ($state < 30) {
                $queryRes = $this->postJson('fbpay.order.query', ['merchant_id' => $config['fb_store_id'], 'merchant_order_sn' => $order['takeOutNo']]);
                if ($queryRes['result_code'] != 200) {
                    if ($queryRes['result_code'] == 9999) {
                        $this->postJson('fbpay.order.close', ['merchant_id' => $config['fb_store_id'], 'merchant_order_sn' => $order['takeOutNo']]);
                        throw new BadRequestHttpException("用户取消支付");
                    }
                    throw new BadRequestHttpException($res['result_message']);
                }
                if ($queryRes['data']['order_status'] == 'SUCCESS') {
                    return $queryRes['data'];
                }
                if (in_array($queryRes['data']['order_status'], ['REVOKED', 'CLOSED', 'REVOKING'])) {
                    throw new BadRequestHttpException("用户取消支付");
                }
                $state++;
                sleep(1);
            }
            throw new BadRequestHttpException('支付时间超时');
        }
        $queryRes = $this->postJson('fbpay.order.query', ['merchant_id' => $config['fb_store_id'], 'merchant_order_sn' => $order['takeOutNo']]);
        if ($queryRes['result_code'] != 200) {
            throw new BadRequestHttpException($res['result_message']);
        }
        return $queryRes['data'];
    }

    public function refund($order, $uniacid, $payId)
    {
        $payConfig = PayTemplate::where('uniacid', $uniacid)->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }
        $config = $payConfig->data;
        $bizContent = [
            'merchant_id' => $config['fb_store_id'],
            'order_sn' => $order['transaction_id'],
            'merchant_refund_sn' => getTakeOutNo('R'),
            'refund_amount' => $order['refund_amount']
        ];
        if ($payConfig->payType == 1) {
            $this->payConfig($config, $uniacid);
            $bizContent['sub_appid'] = WechatPay::getAppId($uniacid);
        }
        $res =  $this->postJson('fbpay.order.refund', $bizContent);
        if ($res['result_code'] != 200) {
            throw new BadRequestHttpException($res['result_message']);
        }
        RefundOrder::create([
            'takeOutNo' => $order['takeOutNo'],
            'refundNo' => $bizContent['merchant_refund_sn'],
            'state' => 1,
            'data' => []
        ]);
        return true;
    }


    /**
     * 生成提交结果参数
     * @param $commonData 公共参数
     * @param array $bizContent 业务参数
     * @return bool|string
     * @throws Exception
     */
    public function postJson($method, $bizContent)
    {
        $commonData['method'] = $method;
        $commonData['nonce'] = wxNonceStr(6);
        $commonData['biz_content'] = json_encode($bizContent);
        $commonData['vendor_sn'] = $this->config['vendor_sn'];
        $commonData['sign'] = $this->getSign($commonData);
        try {
            $result = httpRequest($this->gateway, $commonData, [], 'POST');
            return $result;
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * 字典排序
     * @param $data
     * @return array
     */
    public function arrSort($data)
    {
        //数组键名小写
        $arr = array_change_key_case($data);
        //先进行键升序排列
        ksort($arr);
        return $arr;
    }

    /**
     * 生成签名
     * @param $data
     * @return string
     */
    public function getSign($data)
    {

        $arr = $this->arrSort($data);
        //全部小写合并字符串
        $str = '';
        foreach ($arr as $key => $value) {
            $str .= strtolower($key) . '=' . $value . '&';
        }
        $str = trim($str, '&') . $this->config['secret'];
        //获取待加密字符串
        return strtoupper(md5($str));
    }
}
