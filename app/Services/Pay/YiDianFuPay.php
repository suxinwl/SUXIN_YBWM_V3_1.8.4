<?php

namespace App\Services\Pay;

use App\Models\Order\PayLog;
use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Models\RefundOrder;
use App\Services\BaseService;
use App\Services\ConfigService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * 一点付支付类
 */
class YiDianFuPay extends BaseService
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

    //版本
    public $schema = "LKLAPI-SHA256withRSA";
    //渠道地址
    public $client_host = "https://service.eswhyf.com";

    /**
     * 拉卡拉支付
     */
    public function __construct()
    {
        $this->config = [
            'merchant_no'    => 'M33023803',
            "secret_key"     => 'f0c3f5d5a86aff3ca12020c923adc6c92',
            'appid'         => env('LKL_APPID', 'OP00002477'),
        ];
        $config = ConfigService::getSystemSet('payConfig');

    }


    /**
     * 线上窗口支付
     * @param $order
     * @param $uniacid
     * @param $payId
     * @param $appType
     * @return array|string[]
     */
    public function jsApiPay($order, $uniacid, $payId, $appType = 1, $templateId)
    {

        $payConfig = PayConfig::where('uniacid', $uniacid)->where('id', $payId)->first();

        $notify_url =Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/yidianfu/$payConfig->templateId";
        $config = $payConfig->payTemplate->data;
        $data = [
            //商户订单号
            'order_no'      =>  $order['takeOutNo'],
            //支付备注或描述
            'title'    =>  $order['desc'],
            //收款金额 ps:0.01
            'total_fee'     =>  $order['amount'],
            "ip"    =>  Request()->createFromGlobals()->getClientIp(),
            //回调地址
            'notice_url'     => $notify_url,
            'store_no'=>$config['store_no'],
        ];
        $arr=[
            'merchant_no'=>$config['merchant_no'],
            'secret_key'=>$config['secret_key'],
        ];
        //发起支付
        $app = new YiDianFuPay();
        $res = $app->actionApi("{$this->client_host}/unipay/unipay/pay", $data,$arr);
        $response = [
            //商家订单号
            'takeOutNo'     => $order['takeOutNo'],
            //半屏appid
            'appId'     => "wx4349ecc35b304695",
            //是否收银台
            'is_cashier_desktop'     => 1,
            //支持多次支付
            'support_repeat_pay'     => 1,
            //收银台地址
            'path'     => "h5/pay/pay?counterUrl=".urlencode($res['counter_url']),
        ];
        return $response;


    }


    /**
     * 统一发起请求
     * @param $url
     * @param $params
     * @return mixed
     */
    public function actionApi($url , $params,$arr )
    {
        ksort($params);
        foreach ($params as $key=>$item){
            if(empty($item)){
                unset($params[$key]);
            }
        }

        $params=self::signature($params,$arr);

        $params = json_encode($params, JSON_UNESCAPED_UNICODE);

        $headers = [
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

        if($result['code']=="200"){


            return $result['data'];
        }


        throw new BadRequestHttpException($result['msg']??"系统错误！");
    }

    public function refund($order, $uniacid, $payId)
    {
        $payConfig = PayConfig::where('uniacid', $uniacid)->where('templateId', $payId)->first();
        $config = $payConfig->payTemplate->data;
        $payLog=PayLog::where('orderSn',$order['takeOutNo'])->first();
        $data = [
            'order_no'      =>  $payLog->paySn,
            'amount'     =>  floatval($order['total_amount']),
        ];
        $arr=[
            'merchant_no'=>$config['merchant_no'],
            'secret_key'=>$config['secret_key'],
        ];
        $app = new YiDianFuPay();
        $res = $app->actionApi("{$this->client_host}/unipay/refund/order", $data,$arr);
        return $res;
    }
    /**
     * 签名
     * @param array $data
     * @return array
     * @author cfn <cfn@leapy.cn>
     * @date 2021/11/9
     */
    protected function signature(array $data,$arr)
    {
        $pre_data = [
            'data' => json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            'merchant_no' => $arr['merchant_no'],
            'nonce' => self::getRandomStr(16),
            'timestamp' => time(),
            'version' => '1.0',
        ];
        $pre_data['signature'] = self::createSignature($pre_data,$arr);
        return $pre_data;
    }

    /**
     * 创建签名字符串
     * @param $data
     * @return string
     * @author cfn <cfn@leapy.cn>
     * @date 2021/11/9
     */
    protected function createSignature($data,$arr)
    {
        // 1. 预签名数组 注意按照ascii排序
        ksort($data);
        // 2. 对数据进行k=v拼接并用&连接,内容为空的除外，例如：a=1&b=2
        $pre_str = '';
        foreach($data as $k => $v)
        {
            // 空值不参与签名
            !empty($v) && $pre_str .= $k . "=" . $v . "&";
        }
        // 3. 将auth_key拼接到最后边
        $pre_str .= 'secret_key='.$arr['secret_key'];
        // 4. 对数据进行md5加密，并转大写
        return strtoupper(md5($pre_str));
    }

    /**
     * 随机字符串
     * @param $len
     * @return string
     * @author cfn <cfn@leapy.cn>
     * @date 2021/11/9
     */
    protected function getRandomStr($len)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }
}
