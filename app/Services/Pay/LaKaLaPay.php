<?php

namespace App\Services\Pay;

use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Models\RefundOrder;
use App\Services\BaseService;
use App\Services\ConfigService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * 拉卡拉支付类
 */
class LaKaLaPay extends BaseService
{
    //配置信息
    public $config = [
        //通道Appid
        "appid"             =>  "",
        //证书号
        "certificate_id"    =>  "",
        //服务商私钥
        "private_key"       =>  "",
        //通道方公钥
        "public_key"        =>  "",
    ];
    /**
     * 回调地址
     * @var string[]
     */
    public $nofity_url = [
        "pay"       =>  "/NotifyApi/Lkl/pay",
        "refund"    =>  "/NotifyApi/Lkl/refund",
    ];
    //版本
    public $schema = "LKLAPI-SHA256withRSA";
    //渠道地址
    public $client_host = "https://s2.lakala.com";

    /**
     * 拉卡拉支付
     */
    public function __construct()
    {
        $this->config = [
            'private_key'    => env('LKL_PRIVATE_KEY', '/lkl/private_key.pem'),
            "public_key"     => env('LKL_PUBLIC', '/lkl/public_key.cer'),
            'appid'         => env('LKL_APPID', 'OP00002477'),
            'certificate_id'     => env('LKL_CERTIFICATE_ID', '018e35d8c0bc'),
            'is_cashier_desktop'     => env('LKL_CERTIFICATE_ID', '018e35d8c0bc'),
        ];
        $config = ConfigService::getSystemSet('payConfig');
        if ($config->lklState == 1) {
            $this->config = [
                'private_key'        => $config->lklPrivateKey,
                "public_key"         => $config->lklPublic,
                "appid"              => $config->lklAppid,
                "certificate_id"     => $config->lklCertificateId,
            ];
        }
        $this->config['private_key'] = openssl_get_privatekey(file_get_contents(public_path() .$this->config['private_key']));
        if(!$this->config['private_key']){
            throw new BadRequestHttpException("无法读取本地拉卡拉私钥!");
        }
        if(!file_get_contents(public_path() .$this->config['public_key'])){
            throw new BadRequestHttpException("无法读取本地拉卡拉公钥!");
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
        $app = new LaKaLaPay();
        $trans_amt = number_format(($order['amount']*100),0,'.','');
        $data = [
            //收款方商户号
            'merchant_no'       =>  $config['lkl_shop_id'],
            //设备号
            'term_no'     =>  $config['lkl_term_no'],
            //商户订单号
            'out_trade_no'      =>  $order['takeOutNo'],
            //收款金额 ps:0.01
            'total_amount'     =>  $trans_amt,
            //支付备注或描述
            'subject'    =>  $trans_amt,
            //回调地址
            //'notify_url'     => $notify_url,
            'location_info'       => [
                "request_ip"    =>  Request()->createFromGlobals()->getClientIp(),
            ],
        ];
        Cache::set($order['takeOutNo'],$config['lkl_term_no'],3600*24*7);

        if($config['is_cashier_desktop'] == 1 || ($payConfig->payType == 'weixin' ? 'WECHAT' : "ALIPAY") =="ALIPAY"){
            $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/LaKaLaPay/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/LaKaLaPay/$payConfig->templateId";
            $data['notify_url'] = $notify_url;
            switch ($payConfig->payType == 'weixin' ? 'WECHAT' : "ALIPAY"){
                case "WECHAT"://微信
                    $data['account_type']   = "WECHAT";
                    $data['trans_type']     = "71";
                    $data['acc_busi_fields']    =   [
                        "user_id"   =>  $order['openid'],
                        "sub_appid"   =>  WechatPay::getAppId($uniacid),//小程序APPID
                    ];
                    break;
                case "ALIPAY"://支付宝
                    $data['account_type'] = "ALIPAY";
                    $data['trans_type']     = "51";
                    $data['acc_busi_fields']    =   [
                        "user_id"   =>  $order['openid']
                    ];
                    break;
                default:
                    throw new BadRequestHttpException("错误支付类型!");
                    break;
            }
            //发起支付
            $res = $app->actionApi("{$this->client_host}/api/v3/labs/trans/preorder", $data);
            $app->log($res,"jsapi");
            if ($data['account_type'] == 'WECHAT') {
                $acc_resp_fields = $res['acc_resp_fields'];
                $this->log($acc_resp_fields,"jsapi");
                return  [
                    'timeStamp'     => $acc_resp_fields['time_stamp'],
                    'nonceStr'      => $acc_resp_fields['nonce_str'],
                    'package'       => $acc_resp_fields['package'],
                    'signType'      => $acc_resp_fields['sign_type'],
                    'paySign'       => $acc_resp_fields['pay_sign'],
                    'takeOutNo'     => $order['takeOutNo'],
                ];
            } else {
                $acc_resp_fields = $res['acc_resp_fields'];
                return ['trade_no' => $acc_resp_fields['prepay_id']];
            }
        }elseif($config['is_cashier_desktop'] == 2){//拉卡拉收银台
            $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/LaKaLaPayCashierDesktop/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/LaKaLaPayCashierDesktop/$payConfig->templateId";
            $data = [
                //订单号
                "out_order_no"          =>  $order['takeOutNo'],
                //收款方商户号
                'merchant_no'           =>  $config['lkl_shop_id'],
                //终端号
                'term_no'               =>  $config['lkl_term_no'],
                //截至时间
                'order_efficient_time'  =>  date("YmdHis",time()+900),
                //支付金额
                'total_amount'          =>  $trans_amt,
                //订单描述
                'order_info'            =>  $trans_amt,
                //回调地址
                'notify_url'            => $notify_url,
                //是否退款 0：不支持 1：支持
                'support_refund'        => 1,
            ];
            //发起支付
            $res = $app->actionApi("{$this->client_host}/api/v3/ccss/counter/order/special_create", $data);
            $response = [
                //商家订单号
                'takeOutNo'     => $order['takeOutNo'],
                //半屏appid
                'appId'     => "wxc3e4d1682da3053c",
                //是否收银台
                'is_cashier_desktop'     => 1,
                //支持多次支付
                'support_repeat_pay'     => 1,
                //收银台地址
                'path'     => "payment-cashier/pages/checkout/index?source=WECHATMINI&counterUrl=".urlencode($res['counter_url']),
            ];
            return $response;
        }else{//自研半屏
            $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/CashierDesktop/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/CashierDesktop/$payConfig->templateId";
            $params =[
                "mchId"     =>  $this->config['lkl_out_shop_id'],
                "amount"    =>  bcp($trans_amt,100,2),
                "content"   =>  $trans_amt,
                "orderNo"   =>  $order['takeOutNo'],
                "notify"    =>  $notify_url,
                "tenantId"  =>  7,
            ];
            $appid = "wxc93e1211084f0bc8";
            $response = [
                "takeOutNo" =>  $order['takeOutNo'],
                "appId"     =>  $appid,
                //是否收银台
                'is_cashier_desktop'     => 1,
                'path' =>  "pages/pay/pay_custom1?payload=".json_encode($params,JSON_UNESCAPED_UNICODE).'&appId="'.$appid.'"&immediate=false',
            ];
            return $response;
        }

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
        $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/LaKaLaPay/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/LaKaLaPay/$payConfig->templateId";
        $app = new LaKaLaPay();
        $trans_amt = number_format($order['amount']*100,0,'.','');
        $data = [
            //收款方商户号
            'merchant_no'       =>  $config['lkl_shop_id'],
            //商户订单号
            'out_trade_no'      =>  $order['takeOutNo'],
            //收款金额 ps:0.01
            'total_amount'     =>  $trans_amt,
            //支付授权码
            'auth_code'     =>  $order['auth_code'],
            //支付备注或描述
            'subject'    =>  $trans_amt,
            //IP地址
            'location_info'       => [
                "request_ip"    =>  Request()->createFromGlobals()->getClientIp(),
            ],
            //设备号
            'term_no'     =>  $config['lkl_term_no2'],
            //回调地址
            'notify_url'     => $notify_url,
        ];
        Cache::set($order['takeOutNo'],$config['lkl_term_no2'],3600*24*7);
        $this->log($data,"micropay");
        $res = $app->actionApi("{$this->client_host}/api/v3/labs/trans/micropay", $data);
        $state = 0;
        $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/CashierDesktop/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/CashierDesktop/$payConfig->templateId";
        while ($state < 60) {
            $queryRes = $app->actionApi("{$this->client_host}/api/v3/labs/query/tradequery", [
                'merchant_no'       => $config['lkl_shop_id'],//商户号
                'term_no'           => $config['lkl_term_no2'],//商户终端号
                'out_trade_no'      => $order['takeOutNo'],//商户订单号
            ]);
            //无关单接口

            //支付成功
            if ($queryRes['trade_state'] == 'SUCCESS') {
                $queryRes['out_trans_id'] = $queryRes['trade_no']??'';
                $this->notifyPost($notify_url,['orderStatus'=>"TRADE_SUCCESS","orderNo"=>$order['takeOutNo'],'upOrderNo'  => $queryRes['trade_no']]);
                sleep(0.8);
                return $queryRes;
            }
            if ($queryRes['trade_state'] == 'FAIL') {
                throw new BadRequestHttpException($queryRes['trade_state_desc']??"支付失败,请重新支付!");
            }

            $state++;
            sleep(0.5);
        }
        throw new BadRequestHttpException("支付超时");
    }

    /**
     * 退款查询
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
        $app = new LaKaLaPay();
        $data = [
            "merchant_no"           =>  $config['lkl_shop_id'],
            "term_no"               =>  $config['lkl_term_no'],
            "out_refund_order_no"   =>  $order['takeOutNo'],
        ];
        $result = $app->actionApi("{$this->client_host}/api/v3/labs/relation/idmrefund", $data);
        if(in_array($result['trade_state'],["SUCCESS","PART_REFUND","REFUND"])){
            RefundOrder::create([
                'takeOutNo' => $order['takeOutNo'],
                'refundNo' => $data["order_id"],
                'state' => 1,
                'data' => []
            ]);
            return true;
        }
        throw new BadRequestHttpException($result['msg']??"失败!");
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
        $tradeNo = $order['tradeNo'];
        if(empty($tradeNo)){
            $tradeNo = $order['takeOutNo'];
        }
        $term_no = Cache::get($tradeNo);
        if(!$term_no){
            throw new BadRequestHttpException('超出七日，无法退款!');
        }
        $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/LaKaLaPay/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/LaKaLaPay/$payConfig->templateId";
        $config = $payConfig->data;
        $this->config = $config;
        $app = new LaKaLaPay();
        $data = [
            //收款方商户号
            'merchant_no'       =>  $config['lkl_shop_id'],
            //商户订单号
            'out_trade_no'      =>  getTakeOutNo('R'),
            //原商户订单号
            //'origin_out_trade_no'      =>  $order['takeOutNo'],
            'origin_out_trade_no'      =>  $tradeNo,
            'origin_trade_no'      =>  $order['transaction_id'],
            //设备号
            'term_no'           =>  $term_no,
            //退款描述
            'refund_reason'     =>  $order['refund_amount'],
            //退款金额
            'refund_amount'        =>  number_format(($order['refund_amount']*100),0,'.',''),//退款金额
            //IP地址
            'location_info'             =>  [
                'request_ip'            =>  Request()->createFromGlobals()->getClientIp()
            ]
        ];
        $res = $app->actionApi("{$this->client_host}/api/v3/labs/relation/refund", $data);
        RefundOrder::create([
            'takeOutNo' => $order['takeOutNo'],
            'refundNo' => $data["out_trade_no"],
            'money'     => number_format($order['refund_amount'],2,".",""),
            'state' => 1,
            'notes' => $order['notes']??'',
            'data' => []
        ]);
        return true;
    }


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
        $this->log($result);
    }

    /**
     * 统一发起请求
     * @param $url
     * @param $params
     * @return mixed
     */
    public function actionApi($url , $params )
    {
        ksort($params);
        foreach ($params as $key=>$item){
            if(empty($item)){
                unset($params[$key]);
            }
        }
        $params = [
            "req_time"      =>  date("YmdHis"),
            "version"       => "3.0",
            "req_data"      =>  $params
        ];
        $this->log($params);
        $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        $authorization = $this->getAuthorization($params);
        $headers = [
            "Authorization: " . $authorization,
            "Accept: application/json",
        ];
        array_push($headers,"Content-Type:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);//设置HTTP头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $res = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($res,1);
        if(empty($result)){
            throw new BadRequestHttpException("请求通道方失败!");
        }
        if(empty($result['code']??'')){
            throw new BadRequestHttpException("请求支付渠道失败!");
        }
        if($result['code']=="BBS00000" || $result['code']=="000000" || $result['code']=="BBS10000"){
            $this->log($result);
            return $result['resp_data'];
        }

        $this->log($result,"error");
        throw new BadRequestHttpException($result['msg']??"系统错误！");
    }


    protected function getAuthorization($params)
    {
        $random_str = rand(10000000,99999999);
        $timestamp = time();
        $str = $this->config['appid'] . "\n" . $this->config['certificate_id'] . "\n" . $timestamp . "\n" . $random_str . "\n" . $params . "\n";
        $key = $this->config['private_key'];
        openssl_sign($str, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);
        if(!$signature){
            throw new BadRequestHttpException("本地加签失败!");
        }
        return $this->schema . " appid=\"" . $this->config['appid'] . "\"," . "serial_no=\"" . $this->config['certificate_id'] . "\"," . "timestamp=\"" . $timestamp . "\"," . "nonce_str=\"" . $random_str . "\"," . "signature=\"" . base64_encode($signature) . "\"";
    }

    protected function log($saveData,$name="index"){
        $target_dir = iconv("UTF-8", "GBK","../log/post/lkl/".date("Ym")."/");
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
