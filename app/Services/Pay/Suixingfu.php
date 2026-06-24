<?php

namespace App\Services\Pay;

use app\models\jhPay\sxf\SxfClient;
use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Models\RefundOrder;
use App\Services\BaseService;
use App\Services\ConfigService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use function App\Models\Wechat\Pay\validate;

/**
 * Created by PhpStorm.
 * User: qianqian
 * Date: 2020/2/2
 * Time: 14:29
 */
class Suixingfu extends BaseService
{

    /**
     * 密钥
     * @var string
     */

    public $config = [];

    public function __construct()
    {
        $this->config = [
            'privateKey' => env('SXF_PRIVATE_KEY', 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDdWC8wKxk8I3INWtNM/8qwOK1NG6JsPrVfpie2iZabL6UkW7mHxE/56XRRmljvstAtkRvrGLKswWQ/ZN/TGDuNoezcrwAUvplu6+hO0zTEU7As1f4VRCrhQrHsxo/L76zWKZI3RDgjJepKODti15W4f5SPuK/YHeuXoOxwm9FIabYLhypEzvAakUrOGZIxcRTGb2tXmZQ9fmpop3ch31zmtustXebgJ0KDJ4ZklkISqx2EIsCzXQxL+GKtxsfbr5y+BOr/0e1U2cur19XoQN7FrgI7Iha+dKqvc+9gk8HB941HO4gbhNBGIj6OhMddQLqd0fZGm/88igm8mtZiVEGxAgMBAAECggEAFmRjcbYKeYEEesFjevitoqI5NgHDrruxUZnXjqngqJZrWIBHBqsfhCLP96lrseQfF10EvAXlnYB7CcbEtfBPpgZplfHGSlL15rjK6Z6ISgxFWGPVroUU6XD72v5DcdgvXgManaizHSsqxpNlvpwcs2uEtf1zHKP6P36yLLo2s+J9s3/3/7ibW3anoQL6xE/BUkqLJt9y/T4Vb3xgazKtYlbXl8WuH7p4oVKF6afpnXw/YZCAq1JYmz/zsx2FAuufxMftlw/QvFpYIxj4yG0eeM5ah9hNi3ETcyLT4dh7vievmr+H5NAjvxHhSF/WKxmQ/GKLoIL90UMii9Q3Mp1wAQKBgQDw1bWEuXNXqoNY9fDESZ56BMkAPDst/HRHCxOghyhUCw1J933LqNCZp/M6ayD9j/pW8aKSmoHuSrXPh5MtW5Wz7wr65PX/BLfe1Cy8fIxVHbWyJfIUOH0a8S4vAobf1X2vDiYiUxM5as+nc93abigs/Eu9hJHRNPzgWI6/6wLPgQKBgQDrSEnT5CSR3vLftZ3O79V5218By37DDS4tCQYOr5m+c44QKJ7uwe8WXnEaKFcZ1Bc4LVc971jXg6vLovMdsDAL2lcOfkUyL+NarXX5Kf2ihn/x8CoqEn35r/mlUA77xPWDnyF/QP1/uEbqa3aP14132v9jCUtYQvpJoi8H6m+KMQKBgQC1j3dz4tdYzNyOsYLch9+of3kE62N2DK+ga3JVf+9gRKC1FZbJdbAlVt9gOCk731JMP4hfW4n+mmYsWToUZMocR2cQtJHburPfkjdTtdWZyXcUIdU5d0ihihdWK2KA1pMU6ObI07ZXf/WieRBUvt0c5Os4qfvAK2FExJ6BguuwgQKBgGJ2E/9KgEtTQ8x+0pWhJHMkbLPxlxDFWUebeR94ORzMeu0kMq60FfwEdcx+iUTTzwvBXbsbiNBX1/MWNCt+afzr2HbGPOrtw3VVFgO5oNz88FotKVgF+RYeoJif0kVmfWAhngEFD5D9ax/67NjxWdCIo0usvg0nqlpaNthXMWphAoGBALS4CnDvqm4+C4UShv6f6znX2rlHL/+Al96+OZCd+f/GsW2l0+cyoPysRH7vpbrNnYUC9xSnnT2B+e2yQabKeSHFBlIifov24sct5xi21FGaj6WBq1ctFHSRvf5Azc4BuZDx7VF3fRtBlI2dBpDiSzI8aXZdDYg8gSShl183xrBf'),
            "sxfPublic" => env('SXF_PUBLIC', 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjo1+KBcvwDSIo+nMYLeOJ19Ju4ii0xH66ZxFd869EWFWk/EJa3xIA2+4qGf/Ic7m7zi/NHuCnfUtUDmUdP0JfaZiYwn+1Ek7tYAOc1+1GxhzcexSJLyJlR2JLMfEM+rZooW4Ei7q3a8jdTWUNoak/bVPXnLEVLrbIguXABERQ0Ze0X9Fs0y/zkQFg8UjxUN88g2CRfMC6LldHm7UBo+d+WlpOYH7u0OTzoLLiP/04N1cfTgjjtqTBI7qkOGxYs6aBZHG1DJ6WdP+5w+ho91sBTVajsCxAaMoExWQM2ipf/1qGdsWmkZScPflBqg7m0olOD87ymAVP/3Tcbvi34bDfwIDAQAB'),
            'orgId' => env('SXF_PRGID', '91364657'),
        ];
        $config = ConfigService::getSystemSet('payConfig');
        if ($config->sxfState == 1) {
            $this->config = [
                'privateKey' => $config->privateKey,
                "sxfPublic" => $config->sxfPublic,
                "orgId" => $config->orgId,
            ];
        }
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
        $reqBean = [
            "orgId" => $this->config['orgId'],
            "reqData" => $bizContent,
            "reqId" => time(),
            "signType" => "RSA",
            "timestamp" => date("Ymdhis"),
            "version" => "1.0",
        ];
        $reqBean['sign'] = $this->getSign($reqBean);
        try {
            $result = $this->curlPostContents($url, json_encode($reqBean, 320));
            $signResult = $result["sign"];
            //  result.remove("sign");
            unset($result["sign"]);
            //  String resultStr = RSASignature.getOrderContent(result);

            //sign
            /// String resultSign = RSASignature.encryptBASE64(RSASignature.sign(signContent, privateKey));
            $signContent = $this->getSignContent($result);

            //$verify = $this->verify($signContent, $signResult, $this->config['sxfPublic']);
            if ($result['code'] == "0000") {
                return $result['respData'];
            } else {
                throw new BadRequestHttpException($result['msg']);
            }
        } catch (\Exception $e) {
            throw new BadRequestHttpException('交易异常' . $e->getMessage());
        }
    }

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

    public static function checkResponseSign($data)
    {
        //$model->checkResponseSign($data);
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $encodeArray = array();
        $postMultipart = false;

        //echo  $postBodyString;

        unset($k, $v);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $postBodyString);
        $headers = array('content-type:application/json;charset=UTF8');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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

    public function jsApiPay($order, $uniacid, $payId, $appType = 1)
    {
        $payConfig = PayConfig::where('uniacid', $uniacid)->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }

        $config = $payConfig->payTemplate->data;
        $this->config = $config;
        $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/suixingfu/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/suixingfu/$payConfig->templateId";
        $app = new Suixingfu();
        $data['ordNo'] = $order['takeOutNo'];
        $data['mno'] = $config['sxf_shop_id'];
        $data['amt'] = $order['amount'];
        $data['payType'] = $payConfig->payType == 'weixin' ? 'WECHAT' : "ALIPAY";
        $data['payWay'] = $appType == 'mini' ? "03" : '02';
        $data['subject'] = $order['amount'];
        $data["trmIp"] = Request()->createFromGlobals()->getClientIp();
        $data['userId'] =  $order['openid'];
        $data['notifyUrl'] = $notify_url;
        $data['extend'] = $order['attach'];
        if ($data['payType'] == 'WECHAT' && $config['sxfWindowType'] == 2) {
            $data['appletSource'] = '01';
            $res = $app->actionApi('https://openapi.tianquetech.com/order/appletScanPre', $data);
            if ($res['bizCode'] != '0000') {
                throw new BadRequestHttpException($res['bizMsg']);
            } else {
                return $res;
            }
        } else {
            if ($data['payType'] == 'WECHAT') {

                $data['subAppid'] = WechatPay::getAppId($uniacid);
                $this->saveConfig($uniacid, $data['subAppid'], $config);
            }
            $res = $app->actionApi('https://openapi.tianquetech.com/order/jsapiScan', $data);
            if ($res['bizCode'] != '0000') {
                throw new BadRequestHttpException($res['bizMsg']);
            } else {
                if ($data['payType'] == 'WECHAT') {
                    return  [
                        'timeStamp' => $res['payTimeStamp'],
                        'nonceStr' => $res['paynonceStr'],
                        'package' => $res['payPackage'],
                        'signType' => $res['paySignType'],
                        'paySign' => $res['paySign']
                    ];
                } else {
                    return ['trade_no' => $res['source']];
                }
            }
        }
    }

    public function refund($order, $uniacid, $payId)
    {
        $payConfig = PayTemplate::where('uniacid', $uniacid)->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }

        $config = $payConfig->data;
        $this->config = $config;
        $app = new Suixingfu();
        $data['mno'] = $config['sxf_shop_id'];
        $data["ordNo"] = getTakeOutNo('R');
        $data['origSxfUuid'] = $order['transaction_id'];
        $data['amt'] = $order['refund_amount'];
        $res = $app->actionApi('https://openapi.tianquetech.com/order/refund', $data);
        if ($res['bizCode'] != '0000') {
            return false;
        } else {
            RefundOrder::create([
                'takeOutNo' => $order['takeOutNo'],
                'refundNo' => $data["ordNo"],
                'state' => 1,
                'data' => []
            ]);
            return true;
        }
    }

    public function queryRefund($order, $uniacid, $payId)
    {
        $payConfig = PayTemplate::where('uniacid', $uniacid)->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }

        $config = $payConfig->data;
        $this->config = $config;
        $app = new Suixingfu();
        $data['mno'] = $config['sxf_shop_id'];
        $data["ordNo"] = getTakeOutNo('R');
        $data['origSxfUuid'] = $order['transaction_id'];
        $data['amt'] = $order['refund_amount'];
        $res = $app->actionApi('https://openapi.tianquetech.com/order/refund', $data);
        if ($res['bizCode'] != '0000') {
            return false;
        } else {
            RefundOrder::create([
                'takeOutNo' => $order['takeOutNo'],
                'refundNo' => $data["ordNo"],
                'state' => 1,
                'data' => []
            ]);
            return true;
        }
    }
    public function queryOrder($order, $uniacid, $payId)
    {
        $payConfig = PayTemplate::where('uniacid', $uniacid)->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }

        $config = $payConfig->data;
        $this->config = $config;
        $app = new Suixingfu();
        $data['mno'] = $config['sxf_shop_id'];
        $data["ordNo"] = $order['orderSn'];
        $res = $app->actionApi('https://openapi.tianquetech.com/query/tradeQuery', $data);
        var_dump($res);die;
        if ($res['bizCode'] != '0000') {
            return false;
        } else {
            RefundOrder::create([
                'takeOutNo' => $order['takeOutNo'],
                'refundNo' => $data["ordNo"],
                'state' => 1,
                'data' => []
            ]);
            return true;
        }
    }

    public function saveConfig($uniacid, $appid, $config)
    {
        if (!Cache::get('sxf' . $config['sxf_shop_id'], false)) {
            $app = new Suixingfu();
            $data = [
                //业务参数
                "mno" => $config['sxf_shop_id'], //商户编号
                //"ordNo" => time(), //商户订单号
                "subMchId" => $config['sxf_mch_id'], //子商户号
                "subAppid" => $appid, //微信 subAppId
                "type" => "01",
                "accountType" => "01",
                "jsapiPath" => Request()->getSchemeAndHttpHost()
            ];
            $res = $app->actionApi('https://openapi.tianquetech.com/merchant/weChatPaySet/addConf', $data);
            if ($res['bizCode'] == '0000' || $res['bizCode'] == '0001') {
                Cache::put('sxf' . $appid, true);
            } else {
                throw new BadRequestHttpException($res['bizMsg']);
            }
        }
        return true;
    }

    public function micropay($order, $payId)
    {
        $payConfig = PayConfig::where('uniacid', $order['uniacid'])->where('id', $payId)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }

        $config = $payConfig->payTemplate->data;
        $this->config = $config;
        $notify_url = config('app.isWQ') ? Request()->getSchemeAndHttpHost() . "/addnos/qbwm/idnex.php/channelApi/wxPayNotify/suixingfu/$payConfig->templateId" : Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/suixingfu/$payConfig->templateId";
        $app = new Suixingfu();
        $data['ordNo'] = $order['takeOutNo'];
        $data['mno'] = $config['sxf_shop_id'];
        $data['amt'] = $order['amount'];
        $data['subject'] = $order['amount'];
        $data['authCode'] = $order['auth_code'];
        $data["trmIp"] = Request()->createFromGlobals()->getClientIp();
        if ($data['payType'] == 'WECHAT') {
            $data['subAppid'] = WechatPay::getAppId($order['uniacid']);
            $this->saveConfig($order['uniacid'], $data['subAppid'], $config);
        }
        $res = $app->actionApi('https://openapi.tianquetech.com/order/reverseScan', $data);
        if ($res['bizCode'] != '0000') {
            if ($res['tranSts'] == 'PAYING') {
                $state = 0;
                while ($state < 30) {
                    $queryRes = $app->actionApi('https://openapi.tianquetech.com/query/tradeQuery', [
                        'mno' => $config['sxf_shop_id'],
                        'ordNo' => $order['takeOutNo']
                    ]);
                    if (in_array($queryRes['tranSts'], ['FAIL', 'CLOSED', 'CANCELED'])) {
                        $queryRes = $app->actionApi('https://openapi.tianquetech.com/query/cancel', [
                            'mno' => $config['sxf_shop_id'],
                            'origOrderNo' => $order['takeOutNo']
                        ]);
                        throw new BadRequestHttpException("用户取消支付");
                    }
                    if ($queryRes['tranSts'] == 'SUCCESS') {
                        return $queryRes;
                    }
                    $state++;
                    sleep(1);
                }
                $queryRes = $app->actionApi('https://openapi.tianquetech.com/query/cancel', [
                    'mno' => $config['sxf_shop_id'],
                    'origOrderNo' => $order['takeOutNo']
                ]);
                throw new BadRequestHttpException("支付超时");
            }
            throw new BadRequestHttpException($res['bizMsg']);
        }
        return $res;
    }

    //添加分账方
    public  function setMnoArray($mno, $mnoArray)
    {
        $app = new Suixingfu();
        $data = [
            //业务参数
            "mno" => $mno, //商户编号
            "mnoArray" => $mnoArray, //子商户号
        ];
        $res = $app->actionApi('https://openapi.tianquetech.com/query/ledger/setMnoArray', $data);
        return $res;
    }

    //订单分账
    public  function launchLedger($orderId, $orderType = 1)
    {
        if ($orderType == 1) {
            $tableName = '{{%ybwm_takeout_order}}';
        }
        if ($orderType == 2) {
            $tableName = '{{%ybwm_user_balance_order}}';
        }
        if ($orderType == 3) {
            $tableName = '{{%ybwm_integral_order}}';
        }
        if ($orderType == 4) {
            $tableName = '{{%ybwm_roll_bag_order}}';
        }
        if ($orderType == 5) {
            $tableName = '{{%ybwm_cashier_order}}';
        }
        if ($orderType == 6) {
            $tableName = '{{%ybwm_instore_order}}';
        }
        if ($orderType == 7) {
            $tableName = '{{%ybwm_instore_order}}';
        }
        if ($orderType == 8) {
            $tableName = '{{%ybwm_user_vip_order}}';
        }
        if ($orderType == 9) {
            $tableName = '{{%ybwm_appointment}}';
        }
        $order = (new \yii\db\Query())
            ->from($tableName)
            ->where('id=:id', [':id' => $orderId])->one();
        if ($orderType == 2) {
            $storeSet = Config::getStoreSet('serviceCharge', $order['storeId']);
            if ($storeSet['sxfPayMchId'] and $storeSet['sxfPayStoreId'] and $storeSet['giveMchid'] and $storeSet['sonService'] == 1) {
                if ($storeSet['rule'] == 1) {
                    $res = (new \yii\db\Query())
                        ->select(['id', 'name'])
                        ->from('{{%ybwm_store}}')
                        ->where('uniacid=:uniacid AND isMain=1', [':uniacid' => $order['uniacid']])
                        ->one();
                    $storeId = $res['id'];
                    $storeSet = Config::getStoreSet('serviceCharge', $storeId);
                    if ($storeSet['type'] == 1) {
                        $money = bcmiv(bcmul($order['money'], $storeSet['storeValuePlatformProportion'], 2), 100, 2);
                        $minMoney = $storeSet['storeValueDownMoney'];
                        $money = $money > $minMoney ? $money : $minMoney;
                    } else {
                        $money = $storeSet['storeValueMoney'];
                    }
                } else {
                    if ($storeSet['type'] == 1) {
                        $money = bcmiv(bcmul($order['money'], $storeSet['storeValuePlatformProportion'], 2), 100, 2);
                        $minMoney = $storeSet['storeValueDownMoney'];
                        $money = $money > $minMoney ? $money : $minMoney;
                    } else {
                        $money = $storeSet['storeValueMoney'];
                    }
                }
            }
        } else {
            $bill = (new \yii\db\Query())
                ->from('{{%ybwm_bill}}')
                ->where(['outTradeNo' => $order['outTradeNo']])
                ->one();
            $money = bcsub($bill['money'], $bill['storeActualMoney'], 2) ?: 0;
            $maxMoney = bcmul($bill['money'], 0.3, 2);
            if ($money > $maxMoney) {
                $money = $maxMoney;
            }
        }
        file_put_contents('sxffenzhang.log', '订单' . $order['outTradeNo'] . '分账到' . $money . PHP_EOL, FILE_APPEND);
        $thirdInfo = json_decode($order['thirdInfo'], true);
        $uniacid = $order['uniacid'];
        $storeId = $order['storeId'];
        $config = Config::getStoreSet('serviceCharge', $storeId, $uniacid);
        $app = new Suixingfu();
        $data = [
            "mno" => $thirdInfo['mno'], //分账出款商户编号
            "ordNo" => $thirdInfo['ordNo'], //分账对应的原交易商户订单号（字母、数字、下划线）
            "uuid" => $thirdInfo['uuid'],
            "ledgerAccountFlag" => "01", //00 取消分账  01 分账
            "ledgerRule" => [
                [
                    'allotValue' => $money,
                    'mno' => $config['giveMchid'],
                ]
            ],
            "notifyAddress" => Yii::$app->request->hostInfo . '/channelApi/sxf-notice/launch-ledger', //分账结果通知地址
        ];
        $res = $app->actionApi('https://openapi.tianquetech.com/query/ledger/launchLedger', $data);
        file_put_contents('sxffenzhang.log', json_encode($res) . PHP_EOL, FILE_APPEND);
        if ($res['code'] == '0000') {
            Yii::$app->db->createCommand()->update($tableName, ['profitSharingState' => 3, 'profitBillNo' => $res['reqId']], 'id=:id', ['id' => $orderId])->execute();
            Yii::$app->db->createCommand()->update('{{%ybwm_bill}}', ['profitSharingState' => 3], 'outTradeNo=:outTradeNo', ['outTradeNo' => $order['outTradeNo']])->execute();
            return true;
        } else {
            file_put_contents('sxffenzhang.log', $res['respData']['bizMsg'] . PHP_EOL, FILE_APPEND);
        }
    }


    //对账文件获取
    public  function getFileUrl($billType = '01', $billDate = '')
    {
        $app = new Suixingfu();
        $data = [
            //业务参数
            "billDate" => $billDate ?: date('Ymd', time()), //商户编号
            "billType" => $billType, //00 交易01 结算 02 分账03 硬件交易04 微校交易 05 转账
        ];
        $res = $app->actionApi('https://openapi.tianquetech.com/capital/fileDownload/getFileUrl', $data);
        return $res;
    }
    //分账结果查询
    public  function queryLedgerAccount($orderId, $orderType)
    {
        if ($orderType == 1) {
            $tableName = '{{%ybwm_takeout_order}}';
        }
        if ($orderType == 2) {
            $tableName = '{{%ybwm_user_balance_order}}';
        }
        if ($orderType == 3) {
            $tableName = '{{%ybwm_integral_order}}';
        }
        if ($orderType == 4) {
            $tableName = '{{%ybwm_roll_bag_order}}';
        }
        if ($orderType == 5) {
            $tableName = '{{%ybwm_cashier_order}}';
        }
        if ($orderType == 6) {
            $tableName = '{{%ybwm_instore_order}}';
        }
        if ($orderType == 7) {
            $tableName = '{{%ybwm_instore_order}}';
        }
        if ($orderType == 8) {
            $tableName = '{{%ybwm_user_vip_order}}';
        }
        if ($orderType == 9) {
            $tableName = '{{%ybwm_appointment}}';
        }
        $order = (new \yii\db\Query())
            ->from($tableName)
            ->where('id=:id', [':id' => $orderId])->one();
        $thirdInfo = json_decode($order['thirdInfo'], true);
        $app = new Suixingfu();
        $data = [
            "mno" => $thirdInfo['mno'], //分账出款商户编号
            "ordNo" => $thirdInfo['ordNo'], //分账对应的原交易商户订单号（字母、数字、下划线）
            "uuid" => $thirdInfo['uuid']
        ];
        $res = $app->actionApi('https://openapi.tianquetech.com/query/ledger/queryLedgerAccount', $data);
        return $res;
    }

    //取消分账
    public function ledgerBack($orderId, $orderType)
    {
        if ($orderType == 1) {
            $tableName = '{{%ybwm_takeout_order}}';
        }
        if ($orderType == 2) {
            $tableName = '{{%ybwm_user_balance_order}}';
        }
        if ($orderType == 3) {
            $tableName = '{{%ybwm_integral_order}}';
        }
        if ($orderType == 4) {
            $tableName = '{{%ybwm_roll_bag_order}}';
        }
        if ($orderType == 5) {
            $tableName = '{{%ybwm_cashier_order}}';
        }
        if ($orderType == 6) {
            $tableName = '{{%ybwm_instore_order}}';
        }
        if ($orderType == 7) {
            $tableName = '{{%ybwm_instore_order}}';
        }
        if ($orderType == 8) {
            $tableName = '{{%ybwm_user_vip_order}}';
        }
        if ($orderType == 9) {
            $tableName = '{{%ybwm_appointment}}';
        }
        $order = (new \yii\db\Query())
            ->from($tableName)
            ->where('id=:id', [':id' => $orderId])->one();
        $thirdInfo = json_decode($order['thirdInfo'], true);
        $app = new Suixingfu();
        $data = [
            "mno" => $thirdInfo['mno'], //分账出款商户编号
            'origUuid' => $thirdInfo['uuid'],
            "uuid" => $thirdInfo['uuid'],
            'ledgerRule' => [
                [
                    'mno' => $thirdInfo['mno'],
                    'allotValue' => '0.17',
                ]
            ]
        ];
        $res = $app->actionApi('https://openapi.tianquetech.com/query/ledger/ledgerBack', $data);
        return $res;
    }

    //查询待分账金额
    public function queryLedgerAmt($orderId, $orderType)
    {
        if ($orderType == 1) {
            $tableName = '{{%ybwm_takeout_order}}';
        }
        if ($orderType == 2) {
            $tableName = '{{%ybwm_user_balance_order}}';
        }
        if ($orderType == 3) {
            $tableName = '{{%ybwm_integral_order}}';
        }
        if ($orderType == 4) {
            $tableName = '{{%ybwm_roll_bag_order}}';
        }
        if ($orderType == 5) {
            $tableName = '{{%ybwm_cashier_order}}';
        }
        if ($orderType == 6) {
            $tableName = '{{%ybwm_instore_order}}';
        }
        if ($orderType == 7) {
            $tableName = '{{%ybwm_instore_order}}';
        }
        if ($orderType == 8) {
            $tableName = '{{%ybwm_user_vip_order}}';
        }
        if ($orderType == 9) {
            $tableName = '{{%ybwm_appointment}}';
        }
        $order = (new \yii\db\Query())
            ->from($tableName)
            ->where('id=:id', [':id' => $orderId])->one();
        $thirdInfo = json_decode($order['thirdInfo'], true);
        $app = new Suixingfu();
        $data = [
            "mno" => $thirdInfo['mno'], //分账出款商户编号
            'ordNo' => $thirdInfo['ordNo'],
            "uuid" => $thirdInfo['uuid'],
        ];
        $res = $app->actionApi('https://openapi.tianquetech.com/query/ledger/queryLedgerAmt', $data);
        return $res;
    }
}
