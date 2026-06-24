<?php
namespace App\Services;
use App\Models\OpenWechatAuth;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\PayConfig;
class ZhongYinService
{
    public static function sha256_with_rsa_signData($data, $secretKey)
    {
        $str = $secretKey;
        $str = chunk_split($str, 64, "\n");

        $secretKey = "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
        $pkeyid = openssl_pkey_get_private($secretKey);
        openssl_sign($data, $sign, $pkeyid, OPENSSL_ALGO_SHA256);
        openssl_free_key($pkeyid);
        return base64_encode($sign);
    }

    public static function get_sign_data($arr)
    {

        $data = "";
        ksort($arr);
        foreach ($arr as $value) {
            $data = $data . $value;
        }

        return $data;
    }
    public static function get_sign_datas($arr)
    {

        $data = "";
        ksort($arr);
        foreach ($arr as $value) {
            $data = $data . $value;
        }

        return $data;
    }

    public static function send_post($url, $post_data)
    {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type:application/json",
                'content' => $post_data
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;//聚合后台返回报文为json串经过编码后的纯字符串数据
    }


    public static function verify($data, $serverSign, $publicKey)
    {
        $str = $publicKey;
        $str = chunk_split($str, 64, "\n");
        $publicKey = "-----BEGIN PUBLIC KEY-----\n$str-----END PUBLIC KEY-----\n";
        $pubkeyid = openssl_pkey_get_public($publicKey);
        $r = openssl_verify($data, base64_decode($serverSign), $pubkeyid, OPENSSL_ALGO_SHA256);
        openssl_free_key($pubkeyid);
        return $r;
    }

    public static function h5pay($order){
        $arr = [
            "requestId"=>strval(time()),
            "service"=>"CreateBocCashier",
            "version"=>"2.0",
            "ipAddress"=>"",
            "signType"=>"RSA2",
            "merchantId"=>$payConfig->data['merchantId'],
            "terminalNo"=>"88011746",
            "cashierLanguage"=>"zh_TW",
            "amount"=>bcmul($order->money,100),
            "originalAmount"=>bcmul($order->money,100),
            "productDesc"=>"",
            "payChannel"=>"ALL",
            "DefaultUI"=>"AUTO",
            "groupUserId"=>"",
            "productCode"=>"AUTO",
            "mercOrderNo"=>$order->orderSn,
            "orderDate"=>date('Ymd'),
            "orderTime"=>date('His'),
            "validNumber"=>"",
            "notifyUrl"=>"1.do",
            "pageUrl"=>"2.do",
            "reserved1"=>"",
            "reserved2"=>"",
            "reserved3"=>"",
            "supplier"=>"",
            "referUrl"=>"1.com",
            "businessType"=>"5",
            "hotelName"=>"",
            "checkInTime"=>"",
            "checkOutTime"=>"",
            "flightNumber"=>"",
            "departureTime"=>"",
            "admissionNoticeUrl"=>"",
            "goodsInfo"=>"",
            "totalQuantity"=>"",
            "otherBusinessType"=>"test"
        ];
        return $arr;
    }
    public static function wechatpay($order){
        $payConfig = PayConfig::where('uniacid', $order['uniacid'])->where('payType', 'zhongyin')->where('state', 1)->first();
        $re=OpenWechatAuth::where('uniacid',$order['uniacid'])->first();
        $arr=[
            "terminalNo"=>$payConfig->data['terminalNo'],
            "requestId"=>strval(time()),
            "service"=>"CreateWeChatTrade",
            "version"=>"3.0",
            "ipAddress"=>"127.0.0.1",
            "signType"=>"RSA2",
            "merchantId"=>$payConfig->data['merchantId'],
            "amount"=>bcmul($order['amount'],100),
            "originalAmount"=>bcmul($order['amount'],100),
            "mercOrderNo"=>$order['takeOutNo'],
            "orderDate"=>date('Ymd'),
            "orderTime"=>date('His'),
            "notifyUrl"=>"",
            "subAppId"=>$re->authorizer_appid ,
            "subOpenId"=>$order['openid'],
            "transWay"=>'B4',
        ];

        return $arr;
    }
    public static function qrcodepay($order,$authCode=''){

        $url='https://aas.bocmacau.com/w/rsa/mercapi';
        $payConfig = PayConfig::where('uniacid', $order['uniacid'])->where('payType', 'zhongyin')->where('state', 1)->first();

        //$paychannel WECHATPAY 微信支付    ALIPAY 支付寶  BOCPAY 澳門中銀手機銀行  ALIPAYMO支付寶澳門
        $arr=[
            "trmNo"=>$payConfig->data['trmNo'],
            "requestId"=>strval(time()),
            "service"=>"B2CPay",
            "version"=>"1.0",
            "merchantId"=>$payConfig->data['merchantId'],
            "amount"=>bcmul($order['amount'],100),
            "authCode"=>$authCode,
            "payOrderNo"=>$order['takeOutNo'],
            "ordDt"=>date('Ymd'),
            "ordTm"=>date('His'),
            'subject'=>'testpay',
            "valNum"=>'60',
            "notifyUrl"=>'https://am.qidianwh.com',
        ];
        return $arr;
    }
    public static function pay($order,$payConfig='',$pay_type=1,$authCode='')
    {
        $payConfig = PayConfig::where('uniacid', $order['uniacid'])->where('payType', 'zhongyin')->where('state', 1)->first();
        $url = "https://aas.bocmacau.com/w/rsa/mercapi_ol";
        $uniacid=$order['uniacid'];
        //商户私钥，PKCS1
        $secretKey = $payConfig->data['secretKey'];
        //平台公钥
        $publicKey = file_get_contents('zhongyin_cert/platform_public_key.txt');;
        //商户公钥
        $publicKey1 = $payConfig->data['publicKey'];
        switch ($pay_type) {
            case '1':
                $arr=self::wechatpay($order);
                break;
            case '2':
                $arr=self::qrcodepay($order,$authCode);
                $url = "https://aas.bocmacau.com/w/rsa/mercapi";
                break;
            case '3':
                $arr=self::h5pay($order);
                break;
            default:

                break;
        }

        $arr['notifyUrl']="/channelApi/wxPayNotify/zhongyin/".$uniacid."/".$payConfig->templateId;
        //file_put_contents('1.log',json_encode($arr).PHP_EOL,FILE_APPEND);
        if($pay_type==2){
            $data = self::get_sign_data($arr);
        }else{
            $data = self::get_sign_data($arr);
        }
        $merchantSign = self::sha256_with_rsa_signData($data, $secretKey);

        $arr['merchantSign'] = $merchantSign;

        //请求报文整串url编码
        $post_data = urlencode(json_encode($arr));

        //post请求
        if($pay_type==2){
            //file_put_contents('1.log',json_encode($arr).PHP_EOL,FILE_APPEND);
            $json=http_build_query($arr);
            //$json=htmlspecialchars($json);
            $ch = curl_init($url);
            // 设置cURL选项
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json); // 将数据编码为URL编码字符串
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); // 设置请求头

            $return_data = curl_exec($ch); // 执行cURL请求
            $return_data=urldecode($return_data);
            parse_str($return_data,$return_data);

            if($return_data['result']!=='S'){
                $returnMessage=$return_data['returnMessage']?:'暂不支持此支付方式';
                throw new BadRequestException($returnMessage);die;
            }

            curl_close($ch); // 关闭cURL资源
            return $return_data;
        }else{
            $return_data = urldecode(self::send_post($url, $post_data));

            //echo "返回报文=".$return_data.PHP_EOL."<br>";
            $arr = json_decode($return_data, true);
            if($arr['result']=='F'){
                throw new BadRequestException($arr['returnMessage']);die;
            }
            $res=[
                "appId"=>$arr['appid'],
                "nonceStr"=>$arr['payNoncestr'],
                "package"=>$arr['payPackage'],
                "paySign"=>$arr['paySign'],
                "signType"=>"MD5",
                "timeStamp"=>strval($arr['payTimestamp'])
            ];
            return $res;
        }
    }



}
