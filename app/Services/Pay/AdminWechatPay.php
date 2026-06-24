<?php

namespace App\Services\Pay;

use App\Services\BaseService;
use App\Services\ConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Traits\ResourceTrait;
use Illuminate\Support\Facades\Request;
use App\Models\Wechat\Pay\Application;
use App\Models\CertificateDown;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AdminWechatPay extends BaseService
{

    public static function payConfig()
    {
        $config = ConfigService::getSystemSet('payConfig');
        if (empty($config)) {
            throw new BadRequestException('支付设未配置,请联系总后台管理员配置');
        }
        $certificate = self::saveCert($config->wechatPublicKey, 'certificate', 0);
        $private_key = self::saveCert($config->wechatPrivateKey, 'private_key', 0);
        $getPlatformCert  = self::getPlatformCert($config);
        $config = [
            // 必要配置
            'app_id'             => $config->wechatAppId,                //self::getAppId($uniacid),
            'mch_id'             => $config->mch_id,
            'key'                => $config->key,   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
            // v3 API 秘钥
            'secret_key' => $config->serviceKey,
            'v2_secret_key' => $config->serviceKey,
            'certificate'          => $certificate,
            'private_key'           => $private_key,
            'notify_url'         => '',
            'sandbox' => false,
            'platform_certs' => [
                $getPlatformCert
            ],
            'log' => [
                'default' => 'dev', // 默认使用的 channel，生产环境可以改为下面的 prod
                'channels' => [
                    // 测试环境
                    'dev' => [
                        'driver' => 'single',
                        'path' => storage_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'easywechat.log',
                        'level' => 'debug',
                    ],
                    // 生产环境
                    'prod' => [
                        'driver' => 'daily',
                        'path' => storage_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'easywechat.log',
                        'level' => 'info',
                    ],
                ],
            ],
            'http' => [
                'throw'  => true, // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
                // 'base_uri' => 'https://api.mch.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ],
        ];
        return $config;
    }

    public static function getAppId($uniacid)
    {
        $appType = Request()->header('appType');
        $appType = appType($appType);
        if ($appType == 1) {
            $appType = 'mini';
        } else {
            $appType = 'official';
        }
        $model = ChannelOpenWechat::getConfig($uniacid, $appType);
        return $model->authorizer_appid;
    }


    private static function saveCert($data, $key = 'cert_path', $uniacid = 0)
    {
        if (empty($key)) {
            throw new BadRequestException('请配置微信支付证书');
        }
        $path =  app_path() . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . 'wechat' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . $uniacid;
        $file = $path . DIRECTORY_SEPARATOR . "{$key}.pem";
        // if (file_exists($file)) {
        //     return $file;
        // }
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        if (file_put_contents($file, $data)) {
            return $file;
        }
        return false;
    }

    public static function getPlatformCert($config, $uniacid = 0)
    {
        $config = collect($config)->toArray();
        $key = 'wechatpay_' . $config['serial_no'];
        $path =  app_path() . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . 'wechat' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . $uniacid . DIRECTORY_SEPARATOR;
        $file = $path . DIRECTORY_SEPARATOR . 'wechatpay_platformCert.pem';
        $f = $path . DIRECTORY_SEPARATOR . "private_key.pem";
        $php = base_path() . DIRECTORY_SEPARATOR . 'CertificateDown.php';
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $model = new CertificateDown();
        $res  = $model->run(['key' => $config['serviceKey'], 'mchid' => $config['mch_id'], 'privatekey' => $f, 'serialno' => $config['serial_no'], 'output' => $path]);
        if (!file_exists($file)) {
            throw new BadRequestException('微信支付平台证书保存失败');
        }
        return $file;
    }

    public static function Payment()
    {
        $app = new Application(self::payConfig());
        return $app;
    }
}
