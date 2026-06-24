<?php

namespace App\Services\Pay;

use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Models\RefundOrder;
use App\Services\BaseService;
use App\Services\ConfigService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * 回来米支付通道支付类
 * 2024-06-18
 */
class HuiLaiMiPay extends BaseService
{
    /**
     * 密钥
     * @var string
     */

    public $config = [];

    /**
     * 通道方域名
     * @var string
     */
    public $client_host = "https://mcspp.cloudpnr.com";

    public function __construct()
    {
        $this->config = [
            'privateKey' => env('HLM_PRIVATE_KEY', 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCksqbomc6jEIuLbGWTRI1sEAkcfKlSAM3Wtlm/UYUia/lkQHndE6D/ldaHni+bcnefnYA0rUXqdr7NoT2pFcSC8kpS7CK40cfR0VrXEY16/Od9Hk8Y8RmOAOHJYWhQqCDBmi9Q3abj75gw90pG+7HyWdUXSbC39/IYXg86QbfJJ/G5iSnuACLt3Fxjoq9whPWSR9uRzfr0k9jJlOtvtQbftuZvNhPGuAeRGu5cMBXTfSxlC4AlNlPAWVh1DBbuqZy1Qs96SOE9Rtf+42v0pi2QFvQWKAbGhpdXvhR6OSWSSx5QGzQ2YfhxtjT6vpxpw2NgvGdmUD3gkGujSYhcUPCvAgMBAAECggEAVLwtr4Jik/bRNLxiTjB46l4dsfBZVP1g6dULu6j/bERNHU0m5Z1Rue2nYEL8j2eHMDHiYje7vvO1GyqYBMRURGLP5nXjL1+PBN5HdFtthRdVKkRLwbNZyHufrvJUrxqxL6paKarAUIlWreqs5nRciym4vrCFIUGJ5wuNnhVtrynwTUW9359nz8yBC3fHdCYdcYHLQmMKrDqGhU0NAy/vcytk4wFIzn/0+hOeDiNPSmKTQ2MF22G+cp79Wzx/vwe5KCaDuMJKJr71ROSj/GieAHdFx+Hx0GaarJJmU36FqdK10sU9JYFbrkDVqd+FG/IiY3mOKF3EpVgLoOlrJrz6UQKBgQDZTCCCvjBDEokanVxfM91S2DGuQy0+PtU2epLWbKK+hbxGHwJMceq4Tbw+ntgwhqgewduf87k7VnXhTQ7sc6NCrhLhZ8FKwqegnju3B/TPZ7S8InG33bkRvEsAuVqv6PkATurRfLFf44yt8gIUxo17qmEfvtfn9JPI6N1XTzD6DQKBgQDCCDO11Ge59itj+IetRkkFv+h+0U5CuJceGJ6asmcZkvIjM/URJWOsVJHXzISLExXo+1E2SbI6aNhVzwmj7pHaS3XfOilrjxkHRfnlLH6h44a4iwPnmtNrrYqm/Fdd/FkmgxYtZ+5jkD0WCfUZz2drjKjclkQrE8Ah1SexUNkSqwKBgQC6ozphUb3ePjNomrRWSRPWLj8tsCFSD2HOcyVf6ts1zWRSzhuJUWcLeikvl/uUYnRQRq2/CvWUU8TYCPnCeehGQ4GP/S/2aI7V1FLx+HF7G1YPKX6HMHffFd1N9+Y+pVoJu77Qw8sN00JnvS+wbIHlMAhP3flQD7BHpktGzIwCmQKBgFI5H5CiHgKT6vdGTliDPFaPaHq1P7DOgFtkm3F6wG3581ovnl5RtddFTzhflptIzzQXq+aRfFF8NJDIV5F8e5pB+AVMpFjMHxMR+D/BhzgbLu/tGQ6Aca9jrynsLSnK0gyb6D39mOvgJ8K7HX5+gZj11mkD1Idcq0KrDVL6q8JZAoGBAINSCCPDaLZi8x/kEXK3fwq5iKIyAZLAoy4BrfP96njtPB8g+d5Cpzh+3WgHhbkuW+epC65rPM+DusegRvSzlC9Jz6cS3POG1dRK4GGKBkyitFIxaRyuUStZxS5Wh0gvQHk01Uadvy8oILPO5Zhbpbmo4vfVKHKYDa/e17CdMYO0'),
            "hlmPublic" => env('HLM_PUBLIC', 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApLKm6JnOoxCLi2xlk0SNbBAJHHypUgDN1rZZv1GFImv5ZEB53ROg/5XWh54vm3J3n52ANK1F6na+zaE9qRXEgvJKUuwiuNHH0dFa1xGNevznfR5PGPEZjgDhyWFoUKggwZovUN2m4++YMPdKRvux8lnVF0mwt/fyGF4POkG3ySfxuYkp7gAi7dxcY6KvcIT1kkfbkc369JPYyZTrb7UG37bmbzYTxrgHkRruXDAV030sZQuAJTZTwFlYdQwW7qmctULPekjhPUbX/uNr9KYtkBb0FigGxoaXV74UejklkkseUBs0NmH4cbY0+r6cacNjYLxnZlA94JBro0mIXFDwrwIDAQAB'),
            'orgId' => env('HLM_PRGID', '6666000014334699'),
            'sourceNum' => env('HLM_SOURCE_NUM', 'S20221006YN01'),
        ];
        $config = ConfigService::getSystemSet('payConfig');
        if ($config->hlmState == 1) {
            $this->config = [
                'privateKey'    => $config->hlmPrivateKey,
                "sxfPublic"     => $config->hlmPublic,
                "orgId"         => $config->hlmOrgId,
                "sourceNum"     => $config->hlmSourceNum,
            ];
        }
    }

    /**
     * 线上窗口支付
     * @param $order
     * @param $uniacid
     * @param $payId
     * @param $appType
     * @return array|string[]
     */
    public function jsApiPay($order, $uniacid, $payId, $appType = 1)
    {
        $payConfig = PayConfig::where('uniacid', $uniacid)->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }

        $config = $payConfig->payTemplate->data;
        $this->config = $config;
        $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/huiLaiMiPay/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/huiLaiMiPay/$payConfig->templateId";
        $app = new HuiLaiMiPay();
        $trans_amt = number_format($order['amount'],2,".","");
        if(empty($config['is_cashier_desktop']??null) ||$config['is_cashier_desktop'] == 1 || ($payConfig->payType == 'weixin' ? 'WECHAT' : "ALIPAY") =="ALIPAY"){
            $data = [
                //收款方商户号
                'cust_id'       =>  $config['hlm_shop_id'],
                //设备号
                'device_id'     =>  $config['hlm_device_id'],
                //商户订单号
                'order_id'      =>  $order['takeOutNo'],
                //收款金额 ps:0.01
                'trans_amt'     =>  $trans_amt,
                //支付备注或描述
                'goods_desc'    =>  $order['amount'],
                //IP地址
                'ip_addr'       =>  Request()->createFromGlobals()->getClientIp(),
                //版本号
                'api_version'     =>  "20",
                //回调地址
                'bg_ret_url'     => $notify_url,
                'ret_url'     => $notify_url,
            ];
            switch ($payConfig->payType == 'weixin' ? 'WECHAT' : "ALIPAY"){
                case "WECHAT":
                    $data['pay_type'] = "T_MINIAPP";
                    //小程序APPID
                    $data['mini_app_id'] = WechatPay::getAppId($uniacid);
                    //open_id
                    $data['open_id'] = $order['openid'];
                    break;
                case "ALIPAY":
                    $data['pay_type'] = "A2";
                    //买家的支付宝用户ID
                    $data['buyer_id'] = $order['openid'];
                    //open_id
                    $data['buyer_logon_id'] = $order['openid'];
                    break;
                default:
                    throw new BadRequestHttpException("错误支付类型!");
                    break;
            }
            //发起支付
            $res = $app->actionApi("{$this->client_host}/api/mcsproxypay/v1/usersweep/", $data);
            $app->log($res,"jsapi");
            if ($data['pay_type'] == 'T_MINIAPP') {
                $pay_info = json_decode($res['pay_info'],1);
                $this->log($pay_info,"jsapi");
                return  [
                    'timeStamp' => $pay_info['timeStamp'],
                    'nonceStr'  => $pay_info['nonceStr'],
                    'package'   => $pay_info['package'],
                    'signType'  => $pay_info['signType'],
                    'paySign'   => $pay_info['paySign'],
                    'takeOutNo'   => $order['takeOutNo'],
                    'out_trans_id'   => $order['takeOutNo'],
                ];
            } else {
                return ['trade_no' => $res['party_order_id']];
            }
        }
        elseif($config['is_cashier_desktop'] == 3){
            $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/CashierDesktop/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/CashierDesktop/$payConfig->templateId";
            $params =[
                "mchId"     =>  $this->config['hlm_out_shop_id'],
                "amount"    =>  $trans_amt,
                "content"   =>  $trans_amt,
                "orderNo"   =>  $order['takeOutNo'],
                "notify"    =>  $notify_url,
                "tenantId"  =>  6,
            ];
            $this->log($params);
            $appid = "wxc93e1211084f0bc8";
            $response = [
                "takeOutNo" =>  $order['takeOutNo'],
                "out_trans_id" =>  $order['takeOutNo'],
                "appId"     =>  $appid,
                //是否收银台
                'is_cashier_desktop'     => 1,
                'path' =>  "pages/pay/pay_custom1?payload=".json_encode($params,JSON_UNESCAPED_UNICODE).'&appId="'.$appid.'"&immediate=false',
            ];
            $this->log($response);
            return $response;
        }
        throw new BadRequestHttpException("无匹配支付!");
    }

    /**
     * 反扫支付
     * @param $order
     * @param $payId
     * @return bool|string|void
     */
    public function micropay($order, $payId)
    {
        $payConfig = PayConfig::where('uniacid', $order['uniacid'])->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }

        $config = $payConfig->payTemplate->data;
        $this->config = $config;
        $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/huiLaiMiPay/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/huiLaiMiPay/$payConfig->templateId";
        $app = new HuiLaiMiPay();
        $data = [
            //收款方商户号
            'cust_id'       =>  $config['hlm_shop_id'],
            //商户订单号
            'order_id'      =>  $order['takeOutNo'],
            //收款金额 ps:0.01
            'trans_amt'     =>  number_format($order['amount'],2,".",""),
            //支付授权码
            'auth_code'     =>  $order['auth_code'],
            //支付备注或描述
            'goods_desc'    =>  $order['amount'],
            //IP地址
            'ip_addr'       =>  Request()->createFromGlobals()->getClientIp(),
            //收款员ID
            //'oper_user_id'  =>  "",
            //设备号
            'device_id'     =>  $config['hlm_device_id'],
            //api_version
            'api_version'     =>  "20",
            //回调地址
            'bg_ret_url'     => $notify_url,
        ];
        if ($data['payType'] == 'WECHAT') {
            $data['subAppid'] = WechatPay::getAppId($order['uniacid']);
            $this->saveConfig($order['uniacid'], $data['subAppid'], $config);
        }
        $res = $app->actionApi("{$this->client_host}/api/mcsproxypay/v1/bizscan/", $data);
        $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/CashierDesktop/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/CashierDesktop/$payConfig->templateId";
        if(in_array($res['bank_code'],['SUCCESS','TRADE_SUCCESS'])){
            $res['out_trans_id'] = $res['party_order_id'];
            $this->notifyPost($notify_url,['orderStatus'=>"TRADE_SUCCESS","orderNo"=>$data['order_id'],'upOrderNo'  => $res["party_order_id"]]);
            return $res;
        }elseif($res['bank_code']=="WAIT_BUYER_PAY" || $res['bank_code']=="USERPAYING"){
            $state = 0;
            while ($state < 30) {
                $queryRes = $app->actionApi("{$this->client_host}/api/mcsproxypay/v1/query", [
                    'cust_id'      => $config['hlm_shop_id'],
                    'device_id'    => $data['device_id'],
                    'order_type'   => "P",
                    'order_id'     => $order['takeOutNo'],
                ]);
                //无关单接口

                //支付成功
                if ($queryRes['trans_stat'] == 'S') {
                    $queryRes['out_trans_id'] = $queryRes['party_order_id'];
                    $queryRes['out_trans_id'] = $queryRes['party_order_id'];
                    return $queryRes;
                }
                //支付失败
                if($queryRes['trans_stat'] == "F"){
                    throw new BadRequestHttpException($queryRes['resp_desc']??"支付失败请重新支付!");
                }
                $state++;
                sleep(1);
            }
            throw new BadRequestHttpException("支付超时");
        }elseif($res['bank_code']=="FAIL"){
            throw new BadRequestHttpException($res['bank_message']??"支付失败请重新支付!");
        }
    }


    /**
     * 退款
     * @param $order
     * @param $uniacid
     * @param $payId
     * @return false
     */
    public function queryRefund($order, $uniacid, $payId)
    {
        $payConfig = PayTemplate::where('uniacid', $uniacid)->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }

        $config = $payConfig->data;
        $this->config = $config;
        $app = new HuiLaiMiPay();
        $data = [
            //版本号
            'api_version'       =>  "20",
            //收款方商户号
            'cust_id'           =>  $config['hlm_shop_id'],
            //商户订单号
            'order_id'          =>  getTakeOutNo('R'),
            //原商户订单号
            //'org_order_id'      =>  $order['takeOutNo'],
            'party_order_id'      =>  $order['transaction_id'],
            //退款金额
            'refund_amt'        =>  $order['refund_amount'],
            //设备号
            'device_id'     =>  $config['hlm_device_id'],
            //IP地址
            'ip_addr'           =>  Request()->createFromGlobals()->getClientIp(),
        ];
        $res = $app->actionApi("{$this->client_host}/api/mcsproxypay/v1/refund/", $data);
        RefundOrder::create([
            'takeOutNo' => $order['takeOutNo'],
            'refundNo' => $data["order_id"],
            'state' => 1,
            'data' => []
        ]);
        return true;
    }
    /**
     * 退款
     * @param $order
     * @param $uniacid
     * @param $payId
     * @return false
     */
    public function refund($order, $uniacid, $payId)
    {
        $payConfig = PayTemplate::where('uniacid', $uniacid)->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }
        $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/huiLaiMiPay/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/huiLaiMiPay/$payConfig->templateId";
        $config = $payConfig->data;
        $this->config = $config;
        $app = new HuiLaiMiPay();
        $data = [
            //版本号
            'api_version'       =>  "20",
            //收款方商户号
            'cust_id'           =>  $config['hlm_shop_id'],
            //商户订单号
            'order_id'          =>  getTakeOutNo('R'),
            //原商户订单号
            //'org_order_id'      =>  $order['takeOutNo'],
            'org_order_id'      =>  $order['tradeNo'],
            //设备号
            'device_id'     =>  $config['hlm_device_id'],
            //退款金额
            'refund_amt'        =>  number_format($order['refund_amount'],2,".",""),
            //IP地址
            'ip_addr'           =>  Request()->createFromGlobals()->getClientIp(),
        ];
        $this->log($order);
        if(empty($data['org_order_id'])){
            $queryRes = $app->actionApi("{$this->client_host}/api/mcsproxypay/v1/query", [
                'cust_id'      => $config['hlm_shop_id'],
                'device_id'    => $config['hlm_device_id'],
                'order_type'   => "P",
                'party_order_id'     => $order['transaction_id'],
            ]);
            $data['org_order_id'] = $queryRes['order_id'];
        }
        $res = $app->actionApi("{$this->client_host}/api/mcsproxypay/v1/refund/", $data);
        RefundOrder::create([
            'takeOutNo' => $order['takeOutNo'],
            'refundNo'  => $data["order_id"],
            'money'     => (float)$order["refund_amount"],
            'state' => 1,
            'notes' => $order['notes']??'',
            'data' => []
        ]);
        return true;
    }





    /*----------------------------------------------------分割线-----------------------------------------------------------------------*/

    /**
     * 回调触发
     * @param $url
     * @param $data
     * @return void
     */
    public function notifyPost($url,$params)
    {
        $headers = array('content-type:application/json;charset=UTF8');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);//设置HTTP头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $res = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($res,1);
    }

    /**
     * 生成提交结果参数
     * @param $commonData 公共参数
     * @param array $bizContent 业务参数
     * @return bool|string
     * @throws Exception
     */
    public function actionApi($url, $bizContent = [], $appid = null)
    {
        ksort($bizContent);
        foreach ($bizContent as $key=>$value){
            if(!$value) unset($bizContent[$key]);
        }
        $reqBean = [
            "data"          => json_encode($bizContent,JSON_UNESCAPED_UNICODE),
            "sign"          => $this->getSign($bizContent),
            "source_num"    => $this->config['sourceNum'],
            "sign_type"     => "RSA2",
        ];
        try {
            $result = $this->curlPostContents($url, json_encode($reqBean, JSON_UNESCAPED_UNICODE));
            $this->log([$result,$bizContent]);
            $signResult = $result["sign"];
            //  result.remove("sign");
            unset($result["sign"]);
            //  String resultStr = RSASignature.getOrderContent(result);

            //sign
            /// String resultSign = RSASignature.encryptBASE64(RSASignature.sign(signContent, privateKey));
            //$signContent = $this->getSignContent($result);

            //$verify = $this->verify($signContent, $signResult, $this->config['hlmPublic']);
            if ($result['resp_code'] == "000000" || $result['resp_code'] =="000001") {
                return json_decode($result['data'],1);
            } else {
                throw new BadRequestHttpException($result['resp_desc']??"请求异常请联系客服!");
            }
        } catch (\Exception $e) {
            throw new BadRequestHttpException('交易异常' . $e->getMessage());
        }
    }
    /**
     * 生成签名
     * @param $data
     * @return string
     */
    protected function getSign($data)
    {
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->config['privateKey'], 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $data = $this->getSignContent($data);
        openssl_sign($data, $sign, $res);
        // if ("RSA2" == "RSA") {
        //     openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        // } else {

        // }
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 获取签名内容
     * @param $params
     * @return string
     */
    public function getSignContent($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        foreach ($params as $k => $v) {
            $isarray = is_array($v);
            if ($isarray) {
                $stringToBeSigned .= "$k" . "=" . json_encode($v, 320) . "&";
            } else {
                $stringToBeSigned .= "$k" . "=" . "$v" . "&";
            }
        }
        unset($k, $v);
        $stringToBeSigned = substr($stringToBeSigned, 0, strlen($stringToBeSigned) - 1);
        return $stringToBeSigned;
    }

    /**
     * 验证响应报文
     * @param $paramStr
     * @param $sign
     * @param $rsaPublicKey
     * @return bool
     */
    function verify($paramStr, $sign, $rsaPublicKey)
    {
        // $pubKey = $this->$rsaPublicKey;
        //将字符串格式公私钥转为pem格式公私钥
        $pubKeyPem = $this->format_secret_key($rsaPublicKey, 'pub');
        //$pubKeyPem = $rsaPublicKey;
        //转换为openssl密钥，必须是没有经过pkcs8转换的公钥
        $res = openssl_get_publickey($pubKeyPem);
        //url解码签名
        $signUrl = urldecode($sign);
        //base64解码签名
        $signBase64 = base64_decode($sign);
        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($paramStr, $signBase64, $res);
        //释放资源
        openssl_free_key($res);
        //返回资源是否成功
        return $result;
    }
    function format_secret_key($secret_key, $type)
    {
        // 64个英文字符后接换行符"\n",最后再接换行符"\n"
        $key = (wordwrap($secret_key, 64, "\n", true)) . "\n";
        // 添加pem格式头和尾
        if ($type == 'pub') {
            $pem_key = "-----BEGIN PUBLIC KEY-----\n" . $key . "-----END PUBLIC KEY-----\n";
        } else if ($type == 'pri') {
            $pem_key = "-----BEGIN RSA PRIVATE KEY-----\n" . $key . "-----END RSA PRIVATE KEY-----\n";
        } else {
            echo ('公私钥类型非法');
            exit();
        }
        return $pem_key;
    }



    /**
     * 提交提交结果
     * @param $url 网关地址
     * @param array $data 请求参数
     * @param int $timeout 超时时间
     * @return bool|string
     * @throws Exception
     */
    public function curlPostContents($url, $postBodyString = null)
    {
        $headers = [
            "Accept: application/json",
            "Content-Type:application/json;charset=utf8"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postBodyString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);//设置HTTP头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $reponse = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new \Exception($reponse, $httpStatusCode);
            }
        }

        curl_close($ch);
        return json_decode($reponse, true);
    }

    protected function log($saveData,$name="index"){
        $target_dir = iconv("UTF-8", "GBK","../log/post/hlm/".date("Ym")."/");
        if(!file_exists($target_dir)){
            mkdir($target_dir,0777,true);
        }
        $name = $target_dir.date("Ymd").".log";
        $file = fopen($name,"a");
        fwrite($file , "\n".date("Y-m-d H:i:s"));
        fwrite($file , "\n".json_encode($saveData,JSON_UNESCAPED_UNICODE));
        fclose($file);
    }
}
