<?php

namespace App\Services\Pay;

use App\Models\CertificateDown;
use App\Models\OpenWechat;
use App\Models\OpenWechatAuth;
use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Models\RefundOrder;
use App\Services\BaseService;
use App\Services\ConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Traits\ResourceTrait;
use Illuminate\Support\Facades\Request;
use App\Models\Wechat\Pay\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use EasyWeChat\Payment\Application as v2Application;
use Illuminate\Support\Facades\Log;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use App\Services\ZhongYinService;
class WechatPay extends BaseService
{

    public static function channelConfig($uniacid, $payConfigId, $type = 'pay', $version = 'v3')
    {
        $payTemplate = PayTemplate::where('uniacid', $uniacid)->where('id', $payConfigId)->first();
        if (empty($payTemplate)) {
            throw new BadRequestException('当前微信支付模板不存在');
        }

        $wxConfig = [
            'log' => [
                'default' => 'dev', // 默认使用的 channel，生产环境可以改为下面的 prod
                'channels' => [
                    // 测试环境
                    'dev' => [
                        'driver' => 'single',
                        'path' => storage_path() . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'easywechat.log',
                        'level' => 'debug',
                    ],
                    // 生产环境
                    'prod' => [
                        'driver' => 'daily',
                        'path' => storage_path() . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'easywechat.log',
                        'level' => 'info',
                    ],
                ],
            ],
            'http' => [
                'throw'  => false, // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
                // 'base_uri' => 'https://api.mch.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ],
        ];
        if ($type == 'pay') {
            if ($version == 'v3') {
                $wxConfig = array_merge($wxConfig, $payTemplate->payConfig());
            } else {
                $wxConfig = array_merge($wxConfig, $payTemplate->v2PayConfig());
            }
        }
        if ($type == 'withdrawal') {
            $wxConfig = array_merge($wxConfig, $payTemplate->wxWithdrawalConfig());
        }
        return $wxConfig;
    }

    public static function getAppId($uniacid, $throw = true)
    {
        $appType = Request()->header('appType');
        $appType = appType($appType);
        if ($appType == 1||$appType==10||$appType==11) {
            $appType = 'mini';
        } else {
            $appType = 'official';
            $model = OpenWechatAuth::where('uniacid', $uniacid)->where('type', $appType)->first();
            if (empty($model)) {
                $appType = 'mini';
            }
        }
        $model = ChannelOpenWechat::getConfig($uniacid, $appType, $throw);
        return $model->authorizer_appid;
    }

    public static function cert($config, $uniacid)
    {
        $key = $config['key'];
        $path =  app_path() . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . 'wechat' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . $uniacid;
        $shell = "";
    }

    public static function saveCert($data, $key = 'cert_path', $uniacid, $type = 'pay')
    {
        if (empty($key) || empty($uniacid)) {
            throw new BadRequestException('请配置微信支付证书');
        }
        $path =  app_path() . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . 'wechat' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . $uniacid . DIRECTORY_SEPARATOR . $type;
        $file = $path . DIRECTORY_SEPARATOR . "{$key}.pem";
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        if (file_put_contents($file, $data)) {
            return $file;
        }
        return false;
    }

    public static function saveStoreCert($data, $key = 'cert_path', $uniacid, $type = 'pay', $storeId)
    {
        if (empty($key) || empty($uniacid)) {
            throw new BadRequestException('请配置微信支付证书');
        }
        $path =  app_path() . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . 'wechat' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . $uniacid . DIRECTORY_SEPARATOR . 'store' . DIRECTORY_SEPARATOR . $storeId . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR;
        $file = $path . DIRECTORY_SEPARATOR . "{$key}.pem";
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        if (file_put_contents($file, $data)) {
            return $file;
        }
        return false;
    }


    public static function getNewPlatformCert($config, $uniacid = 0, $type = 'pay')
    {
        $config = collect($config)->toArray();
        $path =  app_path() . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . 'wechat' . DIRECTORY_SEPARATOR . 'cert';
        $file = $path . DIRECTORY_SEPARATOR . 'wechatpay_platformCert.pem';
        return $file;
    }


    public static function Payment($uniacid, $payConfigId, $type = 'pay')
    {
        $app = new Application(self::channelConfig($uniacid, $payConfigId, $type));
        return $app;
    }

    public static function getAppType()
    {
        $appType = Request()->header('appType');
        return appType($appType);
    }

    public static function jsApiPay($order, $uniacid, $payConfigId, $channel = 'mini')
    {
        $payConfig = PayConfig::where('uniacid', $uniacid)->where('payType', 'zhongyin')->where('state', 1)->first();
        if($payConfig){
            return ZhongYinService::pay($order,$payConfig);
        }
        $payConfig = PayConfig::where('uniacid', $uniacid)->where('payType', 'weixin')->where('id', $payConfigId)->where('state', 1)->first();
        if (empty($payConfig)) {
            throw new BadRequestException('微信配置错误');
        }

        $app = self::Payment($uniacid, $payConfig->templateId);
        $notify_url =Request()->getSchemeAndHttpHost() . "/channelApi/wxPayNotify/jsPay/$uniacid/$payConfig->templateId";

        if (isset($app->getConfig()['sp_appid'])) {
            $order = [
                'sp_appid' => $app->getConfig()['sp_appid'],
                'sp_mchid' => $app->getConfig()['sp_mchid'],
                'sub_appid' => $app->getConfig()['sub_appid'],
                'sub_mchid' => $app->getConfig()['sub_mchid'],
                "out_trade_no" => $order['takeOutNo'],
                "description" => $order['desc'],
                "notify_url" => $notify_url,
                'attach' => json_encode(['takeOutNo' => $order['orderSn'], 'userId' => $order['userId'], 'profit_sharing' => $app->getConfig()['profit_sharing']]),
                "amount" => [
                    "total" => intval(bcmul($order['amount'] ?: 0.01, 100, 0)),
                    "currency" => "CNY"
                ],
                "settle_info" => [
                    "profit_sharing" => (bool) $app->getConfig()['profit_sharing'],
                ],
                "payer" => [
                    "sub_openid" => $order['openid'] // <---- 请修改为服务号下单用户的 openid
                ]
            ];

            $response = $app->getClient()->postJson("v3/pay/partner/transactions/jsapi", $order);
            if ($response->isFailed()) {
                throw  new BadRequestException($response->getContent(false));
            }
        } else {
            $order = [
                "mchid" => $app->getConfig()['mch_id'], // <---- 请修改为您的商户号
                "out_trade_no" => $order['takeOutNo'],
                "appid" => $app->getConfig()['app_id'], // <---- 请修改为服务号的 appid
                "description" => $order['desc'],
                "notify_url" => $notify_url,
                'attach' => json_encode(['takeOutNo' => $order['orderSn'], 'userId' => $order['userId'], 'profit_sharing' => $app->getConfig()['profit_sharing']]),
                "amount" => [
                    "total" => intval(bcmul($order['amount'] ?: 0.01, 100, 0)),
                    "currency" => "CNY"
                ],
                "settle_info" => [
                    "profit_sharing" => (bool) $app->getConfig()['profit_sharing'],
                ],
                "payer" => [
                    "openid" => $order['openid'] // <---- 请修改为服务号下单用户的 openid
                ]
            ];

            $response = $app->getClient()->postJson("v3/pay/transactions/jsapi", $order);
            if ($response->isFailed()) {
                throw  new BadRequestException($response->getContent(false));
            }
        }
        $utils = $app->getUtils();
        if ($channel == 'mini') {
            return $utils->buildMiniAppConfig($response->toArray()['prepay_id'], $app->getConfig()['app_id'], "RSA");
        } else {
            return $utils->buildSdkConfig($response->toArray()['prepay_id'], $app->getConfig()['app_id'], "RSA");
        }
    }

    public static function close($order, $uniacid, $payConfigId, $channel = 'mini')
    {
        $payConfig = PayConfig::where('uniacid', $uniacid)->where('payType', 'weixin')->where('id', $payConfigId)->where('state', 1)->first();
        if (empty($payConfig)) {
            throw new BadRequestException('微信配置错误');
        }

        $app = self::Payment($uniacid, $payConfig->templateId);
        if (isset($app->getConfig()['sp_appid'])) {
            $order = [
                'sp_mchid' => $app->getConfig()['sp_mchid'],
                'sub_mchid' => $app->getConfig()['sub_mchid']
            ];
            $response = $app->getClient()->postJson("v3/pay/partner/transactions/out-trade-no/{$order['takeOutNo']}/close", $order);
            if ($response->isFailed()) {
                throw  new BadRequestException($response->getContent(false));
            }
        } else {
            $order = [
                "mchid" => $app->getConfig()['mch_id'], // <---- 请修改为您的商户号
            ];
            $response = $app->getClient()->postJson("v3/pay/transactions/out-trade-no/{$order['takeOutNo']}/close", $order);
            if ($response->isFailed()) {
                throw  new BadRequestException($response->getContent(false));
            }
        }
    }

    public static function refund($order, $uniacid, $payId)
    {
        DB::beginTransaction();
        $payTemplate = PayTemplate::where('uniacid', $uniacid)->where('id', $payId)->first();
        if ($payTemplate->data['payment'] == 2) {
            $app = ChannelOpenWechat::miniProgram($uniacid);
            $unifyData = [
                "openid" => $order['openid'] ?: '',
                "mch_id" => $payTemplate->data['mch_id'],
                'trade_no' => $order['takeOutNo'],
                'transaction_id' => $order['transaction_id'],
                'refund_no' => getTakeOutNo("R"),
                "total_amount" => intval(bcmul($order['total_amount'] ?: 0.01, 100, 0)),
                'refund_amount' => intval(bcmul($order['refund_amount'] ?: 0.01, 100, 0))
            ];
            $res = $app->httpPostJson("shop/pay/refundorder", $unifyData);
            if ($res['errcode'] != 0) {
                throw new BadRequestException($res['errmsg']);
            }
            RefundOrder::create([
                'takeOutNo' => $order['takeOutNo'],
                'refundNo' => $unifyData['refund_no'],
                'state' => 1,
                'data' => []
            ]);
            DB::commit();
            return true;
        }
        $app = self::Payment($uniacid, $payId);
        if (isset($app->getConfig()['sp_appid'])) {
            $unifyData = [
                'sub_mchid' => $app->getConfig()['sub_mchid'],
                'transaction_id' => $order['transaction_id'],
                'out_refund_no' => getTakeOutNo('R'),
                'amount' => [
                    'total' => intval(bcmul($order['total_amount'] ?: 0.01, 100, 0)),
                    'refund' => intval(bcmul($order['refund_amount'] ?: 0.01, 100, 0)),
                    "currency" => "CNY"
                ]
            ];
            $response = $app->getClient()->postJson("v3/refund/domestic/refunds", $unifyData);
            if ($response->isFailed()) {
                throw  new BadRequestException($response->getContent(false));
            }
            RefundOrder::create([
                'takeOutNo' => $order['takeOutNo'],
                'refundNo' => $unifyData['out_refund_no'],
                'state' => 1,
                'data' => $response
            ]);
            DB::commit();
            return true;
        } else {
            $unifyData = [
                'transaction_id' => $order['transaction_id'],
                'out_refund_no' => getTakeOutNo('R'),
                'amount' => [
                    'total' => intval(bcmul($order['total_amount'] ?: 0.01, 100, 0)),
                    'refund' => intval(bcmul($order['refund_amount'] ?: 0.01, 100, 0)),
                    "currency" => "CNY"
                ]
            ];
            $response = $app->getClient()->postJson("v3/refund/domestic/refunds", $unifyData);
            if ($response->isFailed()) {
                throw  new BadRequestException($response->getContent(false));
            }
            RefundOrder::create([
                'takeOutNo' => $order['takeOutNo'],
                'refundNo' => $unifyData['out_refund_no'],
                'state' => 1,
                'data' => $response
            ]);
            DB::commit();
            return true;
        }
    }


    public static function refundQuery($order, $uniacid, $payId)
    {
        DB::beginTransaction();
        $payTemplate = PayTemplate::where('uniacid', $uniacid)->where('id', $payId)->first();
        if ($payTemplate->data['payment'] == 2) {
            $app = ChannelOpenWechat::miniProgram($uniacid);
            $unifyData = [
                "openid" => $order['openid'] ?: '',
                "mch_id" => $payTemplate->data['mch_id'],
                'trade_no' => $order['takeOutNo'],
                'transaction_id' => $order['transaction_id'],
                'refund_no' => getTakeOutNo("R"),
                "total_amount" => intval(bcmul($order['total_amount'] ?: 0.01, 100, 0)),
                'refund_amount' => intval(bcmul($order['refund_amount'] ?: 0.01, 100, 0))
            ];
            $res = $app->httpPostJson("shop/pay/refundorder", $unifyData);
            if ($res['errcode'] != 0) {
                throw new BadRequestException($res['errmsg']);
            }
            RefundOrder::create([
                'takeOutNo' => $order['takeOutNo'],
                'refundNo' => $unifyData['refund_no'],
                'state' => 1,
                'data' => $unifyData
            ]);
            DB::commit();
            return true;
        }
        $app = self::Payment($uniacid, $payId);
        if (isset($app->getConfig()['sp_appid'])) {
            $unifyData = [
                'sub_mchid' => $app->getConfig()['sub_mchid'],
                'transaction_id' => $order['transaction_id'],
                'out_refund_no' => getTakeOutNo('R'),
                'amount' => [
                    'total' => intval(bcmul($order['total_amount'] ?: 0.01, 100, 0)),
                    'refund' => intval(bcmul($order['refund_amount'] ?: 0.01, 100, 0)),
                    "currency" => "CNY"
                ]
            ];
            $response = $app->getClient()->postJson("v3/refund/domestic/refunds", $unifyData);
            if ($response->isFailed()) {
                throw  new BadRequestException($response->getContent(false));
            }
            RefundOrder::create([
                'takeOutNo' => $unifyData['out_trade_no'],
                'refundNo' => $unifyData['out_refund_no'],
                'state' => 1,
                'data' => $response
            ]);
            DB::commit();
            return true;
        } else {
            $unifyData = [
                'transaction_id' => $order['transaction_id'],
                'out_refund_no' => getTakeOutNo('R'),
                'amount' => [
                    'total' => intval(bcmul($order['total_amount'] ?: 0.01, 100, 0)),
                    'refund' => intval(bcmul($order['refund_amount'] ?: 0.01, 100, 0)),
                    "currency" => "CNY"
                ]
            ];
            $response = $app->getClient()->postJson("v3/refund/domestic/refunds", $unifyData);
            if ($response->isFailed()) {
                throw  new BadRequestException($response->getContent(false));
            }
            RefundOrder::create([
                'takeOutNo' => $unifyData['out_trade_no'],
                'refundNo' => $unifyData['out_refund_no'],
                'state' => 1,
                'data' => $response
            ]);
            DB::commit();
            return true;
        }
    }
    public static function micropay($order, $payId)
    {

        $payConfig = PayConfig::where('uniacid', $order['uniacid'])->where('payType', 'zhongyin')->where('state', 1)->first();
        if($payConfig){
            $res =ZhongYinService::pay($order,$payConfig,2,$order['auth_code']);
            return $res;
        }

        $payConfig = PayConfig::where('uniacid', $order['uniacid'])->where('payType', 'weixin')->where('id', $payId)->where('state', 1)->first();
        if (empty($payConfig)) {
            throw new BadRequestException('微信配置错误');
        }
        $app = self::v2Payment($order['uniacid'], $payConfig->templateId);
        $res = $app->pay([
            'body' => $order['desc'],
            'out_trade_no' => $order['takeOutNo'],
            'total_fee' => bcmul($order['amount'], 100),
            'auth_code' => $order['auth_code'],
            'profit_sharing' => $app->getConfig()['profit_sharing'] == 1 ? "Y" : "N"
        ]);
        if ($res['return_code'] == 'FAIL') {
            throw new BadRequestException($res['err_code_des'] ?? $res['return_msg']);
        }
        if ($res['result_code'] == 'FAIL') {
            if ($res['err_code'] == "USERPAYING") {
                $state = 0;
                while ($state < 30) {
                    $queryRes = $app->order->queryByOutTradeNumber($order['takeOutNo']);
                    Log::error($queryRes['trade_state']);
                    if ($queryRes['trade_state'] == "SUCCESS") {
                        $state = 99;
                        return $queryRes;
                    } elseif ($queryRes['trade_state'] == 'REVOKED' || $queryRes['trade_state'] == 'PAYERROR') {
                        //$app->reverse->byOutTradeNumber($order['takeOutNo']);
                        throw new BadRequestException('用户已取消支付');
                    }
                    $state++;
                    sleep(1);
                }
                throw new BadRequestException('支付时间超时');
            } else {
                throw new BadRequestException($res['err_code_des']);
            }
        }
        return $app->order->queryByOutTradeNumber($order['takeOutNo']);
    }

    public static function v2Payment($uniacid, $payConfigId)
    {
        $app = new v2Application(self::channelConfig($uniacid, $payConfigId, 'pay', 'v2'));
        return $app;
    }

    public static function withdrawalApp($uniacid, $storeId)
    {
        $config = collect(ConfigService::getChannelConfig('paymentSet', $uniacid, $storeId))->toArray();
        if (empty($config)) {
            throw  new BadRequestException("请配置打款设置");
        }
        $model = new PayTemplate();
        $model->channel = 'weixin';
        $model->type = 1;
        $model->uniacid = $uniacid;
        $model->data = $config['data'];
        return new Application($model->wxWithdrawalConfig());
    }

    public static function getPlatformCert($config, $uniacid = 0, $type = 'pay')
    {
        $key = 'wechatpay_' . $config['serial_number'];
        $path =  app_path() . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . 'wechat' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . $uniacid . DIRECTORY_SEPARATOR . $type;
        $file = $path . DIRECTORY_SEPARATOR . 'wechatpay_platformCert.pem';
        return $file;
        $f = $path . DIRECTORY_SEPARATOR . "private_key.pem";
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $model = new CertificateDown();
        $res  = $model->run(['key' => $config['mch_secret_key'], 'mchid' => $config['mch_id'], 'privatekey' => $f, 'serialno' => $config['serial_number'], 'output' => $path]);
        if (!file_exists($file)) {
            throw new BadRequestException('微信支付平台证书保存失败');
        }
        return $file;
    }

    public static function profit_sharing($bill, $config)
    {
        if ($bill->sharingState != 0 && $bill->sharingState != 2) {
            return true;
        }
        // self::profit_query($bill, $config);
        // $bill->refresh();
        $app = WechatPay::Payment($bill->uniacid, $bill->payTempId);
        if (isset($app->getConfig()['sp_appid'])) {
            $response = $app->getClient()->get("v3/profitsharing/merchant-configs/" . $app->getConfig()['sub_mchid'], []);
            if ($response->isFailed()) {
                $bill->sharingState = 2;
                $bill->msg = $response->getContent(false);
                $bill->save();
                return true;
            }
            $response = $response->toarray();
            $ratio = $response['max_ratio'] / 10000;
            $response = $app->getClient()->get("v3/profitsharing/transactions/{$bill->thirdNo}/amounts", []);
            if ($response->isFailed()) {
                $bill->sharingState = 2;
                $bill->msg = $response->getContent(false);
                $bill->save();
                return true;
            }
            $response = $response->toarray();
            $money = bcmul(bcdiv($response['unsplit_amount'] * 10, 1000, 3), 994);
            $bill->sharingMoney =  bcdiv(bcmul($money, $ratio), 1000, 2);
        } else {
            $response = $app->getClient()->get("v3/profitsharing/transactions/{$bill->thirdNo}/amounts", []);
            if ($response->isFailed()) {
                $bill->sharingState = 2;
                $bill->msg = $response->getContent(false);
                $bill->save();
                return true;
            }
            $response = $response->toarray();
            $bill->sharingMoney = bcdiv(bcmul(bcdiv($response['unsplit_amount'] * 10, 1000, 3), 994), 1000, 2);
        }
        if ($config->channel == 'weixin' && in_array($config->data['type'], [1, 2])) {
            $config = $config->data['wxfz'] ?? [];
        }
        if (empty($config) || empty($config['receivers'])) {
            $bill->sharingState = 2;
            $bill->msg = "未配置分账接收方参数";
            $bill->save();
            return true;
        }
        if (isset($app->getConfig()['sp_appid'])) {
            $order = [
                "transaction_id" => $bill->thirdNo,
                'out_order_no' => getTakeOutNo(),
                "appid" => $app->getConfig()['app_id'],
                'sub_mchid' => $app->getConfig()['sub_mchid'],
                'sp_appid' => $app->getConfig()['sp_appid'],
                "unfreeze_unsplit" => true,
            ];
            $bill->mchId =  $app->getConfig()['sub_mchid'];
        } else {
            $order = [
                "transaction_id" => $bill->thirdNo,
                'out_order_no' => getTakeOutNo(),
                "appid" => $app->getConfig()['app_id'],
                "unfreeze_unsplit" => true
            ];
            $bill->mchId =  $app->getConfig()['mch_id'];
        }
        $amount =  bcmul($bill->sharingMoney, 100, 2) > bcmul($bill->serverMoney, 100, 2) ?  $bill->serverMoney : $bill->sharingMoney;
        $bill->realityMoney = $amount;
        $platformCertificateSerial = PemUtil::parseCertificateSerialNo(file_get_contents($app->getConfig()['platform_certs'][0]));
        foreach ($config['receivers'] as $key => $v) {
            $receivers[] = [
                'type' => $v['type'],
                'account' => $v['account'],
                'name' => $v['name'],
                //'name' => Rsa::encrypt($v['name'], file_get_contents($app->getConfig()['platform_certs'][0])),
                "amount" => intval(bcmul($amount, intval($v['ratio']))) <= 0 ? 1 : intval(bcmul($amount, intval($v['ratio']))),
                'description' => "分账"
            ];
        }
        $order['receivers'] = $receivers;
        $body = [
            'headers' => ['Wechatpay-Serial' => $platformCertificateSerial],
            "json" => $order
        ];
        $response = $app->getClient()->postJson("v3/profitsharing/orders", $body);
        if ($response->isFailed()) {
            $bill->sharingState = 2;
            $bill->msg = $response->getContent(false);
            $bill->save();
            return true;
        }
        $response = $response->toarray();
        if ($response['state'] == "FINISHED") {
            $bill->sharingState = 1;
        } elseif ($response['state'] == "PROCESSING") {
            $bill->sharingState = 3;
        } else {
            $bill->sharingState = 2;
        }
        $bill->sharingTransaction_id = $response['transaction_id'];
        $bill->sharingSn = $response['sharingSn'];
        $bill->sharingData = $response;
        $bill->msg = null;
        $bill->save();
        return true;
    }

    public static function profit_query($bill, $config)
    {
        $app = WechatPay::Payment($bill->uniacid, $bill->payTempId);
        $query = [
            'transaction_id' => $bill->sharingData['transaction_id'] ?? $bill->thirdNo,
        ];
        if (isset($app->getConfig()['sp_appid'])) {
            $query['sub_mchid'] = $bill->sharingData['sub_mchid'];
        }
        $response = $app->getClient()->get("v3/profitsharing/orders/" . $bill->sharingData['out_order_no'] ?? $bill->sharingSn, ['query' => $query]);
        if ($response->isFailed()) {
            $bill->msg = $response->getContent(false);
            $bill->save();
            return true;
        }
        if ($response['state'] == "FINISHED") {
            $bill->sharingState = 1;
        } elseif ($response['state'] == "PROCESSING") {
            $bill->sharingState = 3;
        } else {
            $bill->sharingState = 2;
        }
        $bill->sharingData = $response->toarray();
        $bill->save();
        return true;
    }


    public static function unfreeze($bill, $config)
    {
        $app = WechatPay::Payment($bill->uniacid, $bill->payTempId);
        if (isset($app->getConfig()['sp_appid'])) {
            $order = [
                "transaction_id" => $bill->thirdNo,
                'out_order_no' => getTakeOutNo(),
                'sub_mchid' => $app->getConfig()['sub_mchid'],
                'description' => "解冻全部剩余资金"
            ];
        } else {
            $order = [
                "transaction_id" => $bill->thirdNo,
                'out_order_no' => getTakeOutNo(),
                "appid" => $app->getConfig()['app_id'],
                'description' => "解冻全部剩余资金"
            ];
        }
        $body = [
            "json" => $order
        ];
        $response = $app->getClient()->postJson("v3/profitsharing/orders/unfreeze", $body);
        if ($response->isFailed()) {
            $bill->msg = $response->getContent(false);
            $bill->save();
            return true;
        }
        if ($response['state'] == "FINISHED") {
            $bill->sharingState = 1;
        } elseif ($response['state'] == "PROCESSING") {
            $bill->sharingState = 3;
        } else {
            $bill->sharingState = 2;
        }
        $bill->sharingTransaction_id = $response['transaction_id'];
        $bill->sharingSn = $response['sharingSn'];
        $bill->sharingData = $response->toarray();
        $bill->save();
        return true;
    }

    public function queryOrder($uniacid,$payId,$order)
    {
        $app = self::Payment($uniacid, $payId);
        $queryRes = $app->order->queryByOutTradeNumber($order['takeOutNo']);
        return $queryRes;
    }
}
