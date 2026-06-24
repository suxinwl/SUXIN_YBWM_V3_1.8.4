<?php

namespace App\Services\AliMini;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use App\Services\BaseService;
use App\Services\ConfigService;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class ChannelMini extends BaseService
{
    public static function getOptions($uniacid)
    {
        $config = ConfigService::getChannelConfig('zfbSetting', $uniacid);
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';
        $options->appId = $config['app_id'];
        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = str_replace(PHP_EOL, '', $config['app_secret_cert']); //私钥
        $options->alipayCertPath = self::saveCert($config['alipay_public_cert_path'], 'alipay_public_cert_path', $uniacid);//支付宝公钥
        $options->alipayRootCertPath = self::saveCert($config['alipay_root_cert_path'], 'alipay_root_cert_path', $uniacid);//付宝根证书
        $options->merchantCertPath = self::saveCert($config['app_public_cert_path'], 'app_public_cert_path', $uniacid);//应用公钥
        // 注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        //$options->alipayPublicKey = str_replace(PHP_EOL, '', "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiSVwNzJM9Zh2Z67hzmcadMGm4rbotjuhSsyJQKGw7LCSAj7l4qbbUYaPaUIszqTBa9/881bNepB+641H2DfDJF01gQ6AqUaVgpqx24MWYPIbxAF8MOA3+KrAFru7oyTZvSoMom81hlApIWcY7G1t8RXvVN89Stp9gTHnu3w3lmwUJAg4RRwrBODXnb4YsFF2Hh61vUX0pf8QJiigojsvTll3y8t/rINov7tynKSCUnuL22iC/ytCcDxxbCjUH14188/1KLm5d0t3O2W2ESzM7agVeF6jaNaxygaq3k9zOwQqDnrc95oeA1RBQIN3fsUWX2LQjgRLHQVweDr0+lrvrwIDAQAB"); //公钥
        //可设置异步通知接收服务地址（可选）
        //可设置AES密钥，调用AES加解密相关接口时需要（可选）

        return $options;
    }

    public static function saveCert($data, $key = 'cert_path', $uniacid = 0, $channel = "alih5")
    {
        if (empty($key)) {
            throw new BadRequestException('请配置支付宝证书');
        }
        $path =  app_path() . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . 'alipay' . DIRECTORY_SEPARATOR . $channel . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . $uniacid;
        $file = $path . DIRECTORY_SEPARATOR . "{$key}.crt";
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        if (file_put_contents($file, $data)) {
            return $file;
        }
        return false;
    }

    public static function setOptions($uniacid)
    {
        Factory::setOptions(self::getOptions($uniacid));
    }

    public static function login($code)
    {
        try {
            $result = Factory::base()->oauth()->getToken($code);
            $responseChecker = new ResponseChecker();
            if ($responseChecker->success($result)) {
                $res = json_decode($result->httpBody, true);
                return $res['alipay_system_oauth_token_response'];
            } else {
                throw new BadRequestException("调用失败，原因：" . $result->msg . "，" . $result->subMsg . PHP_EOL);
            }
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
        }
    }
}
