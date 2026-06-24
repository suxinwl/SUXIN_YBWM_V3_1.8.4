<?php

namespace App\Services\Pay;

use App\Services\BaseService;
use App\Services\ConfigService;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Yansongda\Pay\Pay;

class AdminAliPay extends BaseService
{
    public static function config($notify_url = '')
    {
        $config = ConfigService::getSystemSet('payConfig');
        if (empty($config) || $config->alipay != 1) {
            throw new BadRequestHttpException('支付宝支付已关闭,请联系管理员');
        }
        $config = [
            'alipay' => [
                'default' => [
                    // 必填-支付宝分配的 app_id
                    'app_id' => $config->appId,
                    // 必填-应用私钥 字符串或路径
                    'app_secret_cert' => $config->applyPrivateKey,
                    // 必填-应用公钥证书 路径
                    'app_public_cert_path' => self::saveCert($config->appPublicCert, 'app_public_cert'),
                    // 必填-支付宝公钥证书 路径
                    'alipay_public_cert_path' => self::saveCert($config->publicKey, 'alipay_public_cert'),
                    // 必填-支付宝根证书 路径
                    'alipay_root_cert_path' => '',
                    'return_url' => '',
                    'notify_url' => '',
                    // 选填-第三方应用授权token
                    'app_auth_token' => '',
                    // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                    'service_provider_id' => '2088341761463371',
                    // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                    'mode' => Pay::MODE_SERVICE,
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
                'enable' => false,
                'file' => './logs/alipay.log',
                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'single', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
        ];
        return $config;
    }

    private static function saveCert($data, $key = 'cert_path', $uniacid = 0)
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

    public static function payment($option = [])
    {
        return Pay::alipay(array_merge(self::config(), ['_force' => true]));
    }
}
