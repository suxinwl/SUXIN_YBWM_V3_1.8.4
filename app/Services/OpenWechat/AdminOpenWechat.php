<?php

namespace App\Services\OpenWechat;

use App\Models\Config;
use App\Services\ConfigService;
use EasyWeChat\Factory;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AdminOpenWechat extends BaseService
{
    static  $config = [];

    public static function openPlatform()
    {
        $config = ConfigService::getSystemSet('openWechat');
        if (empty($config)) {
            throw new BadRequestException(__('openWechat.not_config'));
        }
        self::$config = $config;
        $config = [
            'app_id'   => self::$config->kfAppID,
            'secret'   => self::$config->kfAppSecret,
            'token'    => self::$config->msgTokan,
            'aes_key'  => self::$config->msgAppSecret,
            'log' => [
                'level' => 'debug',
                'file' => storage_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'wechat.log',
            ],
            'http' => [
                'max_retries' => 1,
                'retry_delay' => 500,
                'timeout' => 20,
                // 'base_uri' => 'https://api.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ],
        ];
        $openPlatform = Factory::openPlatform($config);
        $cache = new RedisAdapter(app('redis')->connection()->client());
        $openPlatform->rebind('cache', $cache);
        return $openPlatform;
    }

    /**
     * 获取授权链接
     */
    public static function getAuthorizationUrl($url, $authType = 2)
    {
        $app = self::openPlatform();

        $options = [
            //1 表示手机端仅展示公众号；2 表示仅展示小程序，3 表示公众号和小程序都展示。如果为未指定，则默认小程序和公众号都展示。
            'auth_type' => $authType,
        ];
        return  $app->getPreAuthorizationUrl($url, $options);
    }
}
