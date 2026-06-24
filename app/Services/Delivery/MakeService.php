<?php

namespace App\Services\Delivery;

use App\Models\Delivery\Channel;
use App\Models\Delivery\Store;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\Make\Application;
use App\Services\DeliveryService;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class MakeService
{
    public $config = [
        'appid'             => 'wxc9b0ed20944df46c',
        'token' => "a17c598dd5707c92c19f5c32dbb62c343ffd0b07",
        // v3 API 秘钥
        'http' => [
            'throw'  => false, // 状态码非 200、300 时是否抛出异常，默认为开启
            'timeout' => 5.0,
            'base_uri' => 'https://demoapi.99make.com/addons/make_speed/core/public/index.php', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
        ]
    ];


    public static function storeApp($storeId, $uniacid = 0)
    {
        // 'wxc9b0ed20944df46c',
        //"a17c598dd5707c92c19f5c32dbb62c343ffd0b07",
        //'https://demoapi.99make.com/addons/make_speed/core/public/index.php'
        $channelConfig = self::getConfig($storeId, $uniacid);
        $config = [
            'appid'             =>      $channelConfig->config['mkAppid'],
            'token' => $channelConfig->config['mkToken'],
            // v3 API 秘钥
            'http' => [
                'throw'  => false, // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
                'base_uri' => $channelConfig->config['mkUrl']  // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ]
        ];
        return new Application($config);
    }


    public static function getConfig($storeId, $uniacid)
    {
        $config = Store::where('storeId', $storeId)->first();
        $storeId = $config->deliveryType == 1 ? 0 : $storeId;
        $model = Channel::where(function ($q) use ($storeId, $uniacid) {
            return $q->where('storeId', $storeId)->where('uniacid', $uniacid);
        })->where('type', 2)->first();
        if (empty($model)) {
            throw  new BadRequestException('当前门店或者店铺没有马科授权');
        }
        return $model;
    }
}
