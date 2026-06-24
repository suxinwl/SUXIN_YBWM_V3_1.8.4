<?php

namespace App\Services\KuaiShou;

use App\Models\Config;
use App\Models\KuaiShou\MiniProgram\Application;
use App\Services\ConfigService;
use EasyWeChat\Factory;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class KsMiniProgram extends BaseService
{
    static  $config = [];
    /**
     * 获取小程序配置
     */
    public static function getConfig($uniacid)
    {
        $config = [
            'app_id' => 'ks718324143407170966',
            'secret' => 'xO4sQUSJFKoK4RuVQCV-Bw',
            'token' => 'easywechat',
            'aes_key' => '....',

            /**
             * 接口请求相关配置，超时时间等，具体可用参数请参考：
             * https://github.com/symfony/symfony/blob/5.3/src/Symfony/Contracts/HttpClient/HttpClientInterface.php
             */
            'log' => [
                'default' => 'dev', // 默认使用的 channel，生产环境可以改为下面的 prod
                'channels' => [
                    // 测试环境
                    'dev' => [
                        'driver' => 'single',
                        'path' => storage_path('/logs') . '/kuaishou.log',
                        'level' => 'debug',
                    ],
                    // 生产环境
                    'prod' => [
                        'driver' => 'daily',
                        'path' => storage_path('/logs') . '/kuaishou.log',
                        'level' => 'info',
                    ],
                ],
            ],
            'http' => [
                'throw'  => true, // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
                'base_uri' => 'https://open.kuaishou.com', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri

                'retry' => true, // 使用默认重试配置
                //  'retry' => [
                //      // 仅以下状态码重试
                //      'http_codes' => [429, 500]
                //       // 最大重试次数
                //      'max_retries' => 3,
                //      // 请求间隔 (毫秒)
                //      'delay' => 1000,
                //      // 如果设置，每次重试的等待时间都会增加这个系数
                //      // (例如. 首次:1000ms; 第二次: 3 * 1000ms; etc.)
                //      'multiplier' => 3
                //  ],
            ],
        ];
        return $config;
    }
    public static function miniProgram($uniacid = 0)
    {
        $app = new Application(self::getConfig($uniacid));
        $cache = new RedisAdapter(app('redis')->connection()->client());
        $app->rebind('cache', $cache);
        return $app;
    }
}
