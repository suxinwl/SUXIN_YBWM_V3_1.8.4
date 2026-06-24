<?php

namespace App\Models;

use App\Services\Pay\AliPay;
use App\Services\Pay\WechatPay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Yansongda\Pay\Pay;
use Illuminate\Support\Facades\Log;

class PayTemplate extends BaseModel
{
    protected $table = 'pay_template';
    use HasFactory;
    protected $guarded = [];
    protected $casts =  [
        'data' => 'array',
    ];
    protected $attributes =  [
        'storeId' => 0,
    ];


    public function v2PayConfig()
    {
        if ($this->channel == 'weixin') {
            $certificate = WechatPay::saveCert($this->data['mch_secret_cert'], 'certificate', $this->uniacid);
            $private_key = WechatPay::saveCert($this->data['mch_public_cert_path'], 'private_key', $this->uniacid);
            // $getPlatformCert  = WechatPay::getPlatformCert($this->data, $this->uniacid);
            if ($this->data['merchantsType'] == 2) {
                $wxConfig = [
                    'sp_appid' => $this->data['appId'],
                    'sp_mchid' => $this->data['mch_id'],
                    'sub_appid' => WechatPay::getAppId($this->uniacid, false) ?? $this->data['appId'],
                    'sub_mch_id' => $this->data['sub_mch_id'],
                    'app_id'             => $this->data['appId'],             //self::getAppId($uniacid),
                    'mch_id'             => $this->data['mch_id'],
                    'key'                => $this->data['v2_secret_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                    // v3 API 秘钥
                    'secret_key' => $this->data['mch_secret_key'],
                    'v2_secret_key' => $this->data['v2_secret_key'],
                    'cert_path'          => $certificate,
                    'key_path'           => $private_key,
                    'notify_url'         => '',
                    'profit_sharing' => $this->data['wxfz']['profitsharingSwitch'] == 1 ? 1 : 0,
                    'sandbox' => false,
                ];
                if ($this->storeId > 0) {
                    $model = PayConfig::where('uniacid', $this->uniacid)
                        ->where('storeId', 0)
                        ->where('payType', 'weixin')
                        ->first();
                    $data = $model->data;
                    $certificate = WechatPay::saveCert($data['mch_secret_cert'], 'certificate', $this->uniacid);
                    $private_key = WechatPay::saveCert($data['mch_public_cert_path'], 'private_key', $this->uniacid);
                    $wxConfig = [
                        'sp_appid' => $data['appId'],
                        'sp_mchid' => $data['mch_id'],
                        'sub_appid' => WechatPay::getAppId($this->uniacid, false) ?? $data['appId'],
                        'sub_mch_id' => $this->data['sub_mch_id'],
                        'app_id'             => $data['appId'],          //self::getAppId($uniacid),
                        'mch_id'             => $data['mch_id'],
                        'key'                => $data['v2_secret_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                        // v3 API 秘钥
                        'secret_key' => $data['mch_secret_key'],
                        'v2_secret_key' => $data['v2_secret_key'],
                        'cert_path'          => $certificate,
                        'key_path'           => $private_key,
                        'profit_sharing' => $this->data['wxfz']['profitsharingSwitch'] == 1 ? 1 : 0,
                        'notify_url'         => '',
                        'sandbox' => false,
                    ];
                }
            } else {
                $wxConfig = [
                    // 必要配置
                    'app_id'             => WechatPay::getAppId($this->uniacid, false) ?? $this->data['appId'],            //self::getAppId($uniacid),
                    'mch_id'             => $this->data['mch_id'],
                    'key'                => $this->data['v2_secret_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                    // v3 API 秘钥
                    'secret_key' => $this->data['mch_secret_key'],
                    'v2_secret_key' => $this->data['v2_secret_key'],
                    'certificate'          => $certificate,
                    'private_key'           => $private_key,
                    'notify_url'         => '',
                    'profit_sharing' => $this->$this->data['wxfz']['profitsharingSwitch'] == 1 ? 1 : 0,
                    'sandbox' => false,
                ];
            }
            return $wxConfig;
        } else {
            $config = [
                'alipay' => [
                    'default' => [
                        // 必填-支付宝分配的 app_id
                        'app_id' => $this->data['app_id'],
                        // 必填-应用私钥 字符串或路径
                        'app_secret_cert' => $this->data['app_secret_cert'],
                        // 必填-应用公钥证书 路径
                        'app_public_cert_path' => AliPay::saveCert($this->data['app_public_cert_path'], 'app_public_cert_path', $this->uniacid),
                        // 必填-支付宝公钥证书 路径
                        'alipay_public_cert_path' => AliPay::saveCert($this->data['alipay_key_cert_path'], 'app_public_cert_path', $this->uniacid),
                        // 必填-支付宝根证书 路径
                        'alipay_root_cert_path' => '',
                        'return_url' => '',
                        'notify_url' => '',
                        // 选填-第三方应用授权token
                        'app_auth_token' => '',
                        // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                        'service_provider_id' => '',
                        // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                        'mode' => Pay::MODE_NORMAL,
                    ]
                ],
                'wechat' => [
                    'default' => [
                        // 必填-商户号，服务商模式下为服务商商户号
                        'mch_id' => '',
                        // 必填-商户秘钥
                        'mch_secret_key' => '',
                        // 必填-商户私钥 字符串或路径
                        'mch_secret_cert' => '',
                        // 必填-商户公钥证书路径
                        'mch_public_cert_path' => '',
                        // 必填
                        'notify_url' => 'https://yansongda.cn/wechat/notify',
                        // 选填-公众号 的 app_id
                        'mp_app_id' => '2016082000291234',
                        // 选填-小程序 的 app_id
                        'mini_app_id' => '',
                        // 选填-app 的 app_id
                        'app_id' => '',
                        // 选填-合单 app_id
                        'combine_app_id' => '',
                        // 选填-合单商户号
                        'combine_mch_id' => '',
                        // 选填-服务商模式下，子公众号 的 app_id
                        'sub_mp_app_id' => '',
                        // 选填-服务商模式下，子 app 的 app_id
                        'sub_app_id' => '',
                        // 选填-服务商模式下，子小程序 的 app_id
                        'sub_mini_app_id' => '',
                        // 选填-服务商模式下，子商户id
                        'sub_mch_id' => '',
                        // 选填-微信公钥证书路径, optional，强烈建议 php-fpm 模式下配置此参数
                        'wechat_public_cert_path' => [
                            '45F59D4DABF31918AFCEC556D5D2C6E376675D57' => __DIR__ . '/Cert/wechatPublicKey.crt',
                        ],
                        // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SERVICE
                        'mode' => Pay::MODE_SERVICE,
                    ]
                ],
                'logger' => [
                    'enable' => true,
                    'file' => storage_path('/logs/alipay.log'),
                    'level' => 'debug', // 建议生产环境等级调整为 info，开发环境为 debug
                    'type' => 'single', // optional, 可选 daily.
                    'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
                ],
            ];
            return $config;
        }
    }

    public function payConfig($notify = "")
    {
        if ($this->channel == 'weixin') {
            if ($this->data['merchantsType'] == 2) {
                if ($this->storeId > 0) {
                    $model = PayConfig::where('uniacid', $this->uniacid)
                        ->where('storeId', 0)
                        ->where('payType', 'weixin')
                        ->first();
                    $data = $model->data;
                    $certificate = WechatPay::saveCert($data['mch_secret_cert'], 'certificate', $this->uniacid);
                    $private_key = WechatPay::saveCert($data['mch_public_cert_path'], 'private_key', $this->uniacid);
                    $getPlatformCert  = WechatPay::getPlatformCert($model->data, $this->uniacid);
                    $wxConfig = [
                        'sp_appid' => $data['appId'],
                        'sp_mchid' => $data['mch_id'],
                        'sub_appid' => WechatPay::getAppId($this->uniacid, false) ?? $data['appId'],
                        'sub_mchid' => $this->data['sub_mch_id'],
                        'app_id'             => WechatPay::getAppId($this->uniacid, false) ?? $data['appId'],             //self::getAppId($uniacid),
                        'mch_id'             => $data['mch_id'],
                        'key'                => $data['mch_secret_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                        // v3 API 秘钥
                        'secret_key' => $data['mch_secret_key'],
                        'v2_secret_key' => $data['mch_secret_key'],
                        'certificate'          => $certificate,
                        'private_key'           => $private_key,
                        'notify_url'         => '',
                        'sandbox' => false,
                        'profit_sharing' => $this->data['wxfz']['profitsharingSwitch'] == 1 ? 1 : 0,
                        'platform_certs' => [
                            $getPlatformCert
                        ],
                    ];
                } else {
                    $certificate = WechatPay::saveCert($this->data['mch_secret_cert'], 'certificate', $this->uniacid);
                    $private_key = WechatPay::saveCert($this->data['mch_public_cert_path'], 'private_key', $this->uniacid);
                    $getPlatformCert  = WechatPay::getPlatformCert($this->data, $this->uniacid);
                    $wxConfig = [
                        'sp_appid' => $this->data['appId'],
                        'sp_mchid' => $this->data['mch_id'],
                        'sub_appid' => WechatPay::getAppId($this->uniacid, false) ?? $this->data['appId'],
                        'sub_mchid' => $this->data['sub_mch_id'],
                        'app_id'             => WechatPay::getAppId($this->uniacid, false) ?? $this->data['appId'],             //self::getAppId($uniacid),
                        'mch_id'             => $this->data['mch_id'],
                        'key'                => $this->data['mch_secret_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                        // v3 API 秘钥
                        'secret_key' => $this->data['mch_secret_key'],
                        'v2_secret_key' => $this->data['mch_secret_key'],
                        'certificate'          => $certificate,
                        'private_key'           => $private_key,
                        'notify_url'         => '',
                        'sandbox' => false,
                        'sharing' => false,
                        'profit_sharing' => $this->data['wxfz']['profitsharingSwitch'] == 1 ? 1 : 0,
                        'platform_certs' => [
                            $getPlatformCert
                        ],
                    ];
                }
            } else {
                $certificate = WechatPay::saveCert($this->data['mch_secret_cert'], 'certificate', $this->uniacid);
                $private_key = WechatPay::saveCert($this->data['mch_public_cert_path'], 'private_key', $this->uniacid);
                $getPlatformCert  = WechatPay::getPlatformCert($this->data, $this->uniacid);
                $wxConfig = [
                    // 必要配置
                    'app_id'             => WechatPay::getAppId($this->uniacid, false) ?? $this->data['appId'],            //self::getAppId($uniacid),
                    'mch_id'             => $this->data['mch_id'],
                    'key'                => $this->data['mch_secret_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                    // v3 API 秘钥
                    'secret_key' => $this->data['mch_secret_key'],
                    'v2_secret_key' => $this->data['mch_secret_key'],
                    'certificate'          => $certificate,
                    'private_key'           => $private_key,
                    'notify_url'         => '',
                    'profit_sharing' => $this->data['wxfz']['profitsharingSwitch'] == 1 ? 1 : 0,
                    'sandbox' => false,
                    'platform_certs' => [
                        $getPlatformCert
                    ],
                ];
            }
            return $wxConfig;
        } elseif ($this->channel == 'alipay') {
            if ($this->storeId > 0) {
                $model = PayConfig::where('uniacid', $this->uniacid)
                    ->where('storeId', 0)
                    ->where('payType', 'alipay')
                    ->first();
                $data = $model->data;
                $default = [
                    // 必填-支付宝分配的 app_id
                    'app_id' => $data['app_id'],
                    'seller_id' => $this->data['seller_id'],
                    // 必填-应用私钥 字符串或路径
                    'app_secret_cert' => $data['app_secret_cert'],
                    // 必填-应用公钥证书 路径
                    'app_public_cert_path' => AliPay::saveCert($data['app_public_cert_path'], 'app_public_cert_path', $this->uniacid),
                    // 必填-支付宝公钥证书 路径
                    'alipay_public_cert_path' => AliPay::saveCert($data['alipay_public_cert_path'], 'alipay_public_cert_path', $this->uniacid),
                    // 必填-支付宝根证书 路径
                    'alipay_root_cert_path' => AliPay::saveCert($data['alipay_root_cert_path'], 'alipay_root_cert_path', $this->uniacid),
                    'return_url' => '',
                    'notify_url' => $notify,
                    // 选填-第三方应用授权token
                    'app_auth_token' => '',
                    // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                    'service_provider_id' => '',
                    // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                    'mode' => Pay::MODE_NORMAL,
                ];
            } else {
                $default = [
                    // 必填-支付宝分配的 app_id
                    'app_id' => $this->data['app_id'],
                    'seller_id' => $this->data['seller_id'],
                    // 必填-应用私钥 字符串或路径
                    'app_secret_cert' => $this->data['app_secret_cert'],
                    // 必填-应用公钥证书 路径
                    'app_public_cert_path' => AliPay::saveCert($this->data['app_public_cert_path'], 'app_public_cert_path', $this->uniacid),
                    // 必填-支付宝公钥证书 路径
                    'alipay_public_cert_path' => AliPay::saveCert($this->data['alipay_public_cert_path'], 'alipay_public_cert_path', $this->uniacid),
                    // 必填-支付宝根证书 路径
                    'alipay_root_cert_path' => AliPay::saveCert($this->data['alipay_root_cert_path'], 'alipay_root_cert_path', $this->uniacid),
                    'return_url' => '',
                    'notify_url' => $notify,
                    // 选填-第三方应用授权token
                    'app_auth_token' => '',
                    // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                    'service_provider_id' => '',
                    // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                    'mode' => Pay::MODE_NORMAL,
                ];
            }
            $config = [
                'alipay' => [
                    'default' => $default
                ],
                'wechat' => [
                    'default' => [
                        // 必填-商户号，服务商模式下为服务商商户号
                        'mch_id' => '',
                        // 必填-商户秘钥
                        'mch_secret_key' => '',
                        // 必填-商户私钥 字符串或路径
                        'mch_secret_cert' => '',
                        // 必填-商户公钥证书路径
                        'mch_public_cert_path' => '',
                        // 必填
                        'notify_url' => '',
                        // 选填-公众号 的 app_id
                        'mp_app_id' => '',
                        // 选填-小程序 的 app_id
                        'mini_app_id' => '',
                        // 选填-app 的 app_id
                        'app_id' => '',
                        // 选填-合单 app_id
                        'combine_app_id' => '',
                        // 选填-合单商户号
                        'combine_mch_id' => '',
                        // 选填-服务商模式下，子公众号 的 app_id
                        'sub_mp_app_id' => '',
                        // 选填-服务商模式下，子 app 的 app_id
                        'sub_app_id' => '',
                        // 选填-服务商模式下，子小程序 的 app_id
                        'sub_mini_app_id' => '',
                        // 选填-服务商模式下，子商户id
                        'sub_mch_id' => '',
                        // 选填-微信公钥证书路径, optional，强烈建议 php-fpm 模式下配置此参数
                        'wechat_public_cert_path' => [
                            '45F59D4DABF31918AFCEC556D5D2C6E376675D57' => __DIR__ . '/Cert/wechatPublicKey.crt',
                        ],
                        // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SERVICE
                        'mode' => Pay::MODE_SERVICE,
                    ]
                ],
                'logger' => [
                    'enable' => true,
                    'file' => './logs/alipay.log',
                    'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                    'type' => 'single', // optional, 可选 daily.
                    'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
                ],
                'http' => [ // optional
                    'timeout' => 60.0,
                    'connect_timeout' => 60.0,
                    // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
                ],
            ];
            return $config;
        }
    }


    public function wxWithdrawalConfig()
    {
        if ($this->data['merchantsType'] == 2) {
            $certificate = WechatPay::saveCert($this->data['sub_mch_cert'], 'certificate', $this->uniacid, 'withdrawal');
            $private_key = WechatPay::saveCert($this->data['sub_key_cert'], 'private_key', $this->uniacid, 'withdrawal');
            $wxConfig = [
                'serial_number' => $this->data['sub_serial_number'],
                'mch_secret_key' => $this->data['sub_cert_key'],
                'mch_id' => $this->data['sub_mch_id'],
            ];
            $getPlatformCert  = WechatPay::getPlatformCert($wxConfig, $this->uniacid, 'withdrawal');
            $config = [
                // 必要配置
                'app_id'             => WechatPay::getAppId($this->uniacid),            //self::getAppId($uniacid),
                'mch_id'             => $this->uniacid['mch_id'],
                'key'                => $this->uniacid['mch_secret_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                // v3 API 秘钥
                'secret_key' => $this->uniacid['mch_secret_key'],
                'v2_secret_key' => $this->uniacid['mch_secret_key'],
                'certificate'          => $certificate,
                'private_key'           => $private_key,
                'notify_url'         => '',
                'sandbox' => false,
                'platform_certs' => [
                    $getPlatformCert
                ],
            ];
        } else {
            $certificate = WechatPay::saveCert($this->data['mch_secret_cert'], 'certificate', $this->uniacid, 'withdrawal');
            $private_key = WechatPay::saveCert($this->data['mch_public_cert_path'], 'private_key', $this->uniacid, 'withdrawal');
            $getPlatformCert  = WechatPay::getPlatformCert($this->data, $this->uniacid, 'withdrawal');
            $config = [
                // 必要配置
                'app_id'             => WechatPay::getAppId($this->uniacid),            //self::getAppId($uniacid),
                'mch_id'             => $this->data['mch_id'],
                'key'                => $this->data['mch_secret_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                // v3 API 秘钥
                'secret_key' => $this->data['mch_secret_key'],
                'v2_secret_key' => $this->data['mch_secret_key'],
                'certificate'          => $certificate,
                'private_key'           => $private_key,
                'notify_url'         => '',
                'sandbox' => false,
                'platform_certs' => [
                    $getPlatformCert
                ],
            ];
        }
        return $config;
    }
}
