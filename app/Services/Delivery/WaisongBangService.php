<?php

namespace App\Services\Delivery;

use App\Models\Delivery\Channel;
use App\Models\Delivery\Store as DeliveryStore;
use App\Models\Store;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\WaiSongBang\Application;
use App\Services\ConfigService;
use App\Services\DeliveryService;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WaisongBangService
{

    public static function app()
    {
        $config = config('app.waisongbang.rel');
        $deliverySetting = ConfigService::getSystemSet('deliverySetting');
        $config = [
            // 'mch_id'             => $channelConfig->channelId,
            // 'deliveryType' => $channelConfig->storeId == 0 ? 1 : 2,
            'mch_id'             => 0,
            'deliveryType' => 1,
            'appkey' => $config['app_key'],
            'thirdPartnerId' => $deliverySetting->third_partner_id ?? 0,
            // v3 API 秘钥
            'secretKey' => $config['app_secret'],
            'http' => [
                'throw'  => false, // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
                'base_uri' => $config['url'], // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ]
        ];
        return new Application($config);
    }

    public static function storeApp($storeId, $uniacid)
    {
        $baseConfig = config('app.waisongbang.rel');
        $channelConfig = self::getConfig($storeId, $uniacid);
        $config = ConfigService::getSystemSet('deliverySetting');
        $config = [
            'mch_id'             => $channelConfig->channelId,
            'deliveryType' => $channelConfig->storeId == 0 ? 1 : 2,
            'appkey' => $baseConfig['app_key'],
            // v3 API 秘钥
            'thirdPartnerId' => $config->third_partner_id,
            'secretKey' => $baseConfig['app_secret'],
            'http' => [
                'throw'  => false, // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
                'base_uri' => $baseConfig['url'], // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ]
        ];
        return new Application($config);
    }

    public static function getConfig($storeId, $uniacid)
    {
        $config = DeliveryStore::where('storeId', $storeId)->first();
        $storeId = $config->deliveryType == 1 ? 0 : $storeId;
        $model = Channel::where(function ($q) use ($storeId, $uniacid) {
            return $q->where('storeId', $storeId)->where('uniacid', $uniacid);
        })->where('type', 3)->first();
        if (empty($model)) {
            throw  new BadRequestException('当前门店或者店铺没有外送帮授权');
        }
        return $model;
    }

    public static function  createPartner($data = [], $update = false)
    {
        $app = self::app();
        $uri = $update ? '/OpenApi/partner/update?v=1.2.5' : '/OpenApi/partner/create';
        $res =  $app->getClient()->postJson($uri, [
            "name" => $data['name'],
            'mobile' => $data['mobile'],
            'store_independent_recharge' => 1,
            'third_partner_id' => $data['third_partner_id'],
            'callback_url' => $data['callback_url'],
        ])->toArray();
        if ($res['code'] != 0 && $res['msg'] != '合作商已经存在') {
            throw new BadRequestHttpException($res['msg']);
        }
        return true;
    }

    /**
     * 创建门店
     */
    public static function  createStore($storeId = 0)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $app = self::app();
        $data = [
            'name' => $store->name,
            'owner_name' => $store->contact,
            'tel' => $store->mobile,
            'city' => $store->regionFormat ? $store->regionFormat[1] : '',
            'area' => $store->regionFormat ? $store->regionFormat[2] : '',
            'address' => $store->address,
            'lat' => $store->lat,
            'lng' => $store->lng,
            'open_start' => '00:00:00',
            'open_end' => '23:59:59'
        ];
        $res =  $app->getClient()->postJson("/OpenApi/store/create", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        return $res['data'];
    }

    /**
     * 开通门店配送
     */
    public static function  createDeliverShop($storeId, $uniacid, $ship_way)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $model = Channel::where('uniacid', $uniacid)->where('storeId', $storeId)->where('type', 3)->first();
        if (empty($model)) {
            throw new BadRequestException('请先授权开通门店');
        }
        $app = self::storeApp($storeId, $uniacid);
        $data = [
            'third_partner_id' => $app->getMerchant()->getThirdPartnerId(),
            'store_id' => $app->getMerchant()->getMerchantId(),
            'ship_way' => $ship_way
        ];
        $res =  $app->getClient()->postJson("/OpenApi/deliver_shop/create", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        $model->config = collect($model->config)->map(function ($item) use ($res) {
            if ($item['ship_way'] == $res['data']['ship_way']) {
                $item['state'] = $res['data']['apply_status'];
            }
            return $item;
        })->toArray();
        $model->save();
        return $model->config;
    }

    /**
     * 开通门店配送状态
     */
    public static function  deliverShopState($storeId, $uniacid, $ship_way)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $model = Channel::where('uniacid', $uniacid)->where('type', 3)->first();
        if (empty($model)) {
            throw new BadRequestException('请先授权开通门店');
        }
        $app = self::storeApp($storeId, $uniacid);
        $data = [
            'third_partner_id' => $app->getMerchant()->getThirdPartnerId(),
            'store_id' => $app->getMerchant()->getMerchantId(),
            'ship_way' => $ship_way
        ];
        $res =  $app->getClient()->postJson("/OpenApi/deliver_shop/create_status", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        $model->config = collect($model->config)->map(function ($item) use ($res) {
            if ($item['ship_way'] == $res['data']['ship_way']) {
                $item['state'] = $res['data']['apply_status'];
            }
            return $item;
        })->toArray();
        $model->save();
        return $res['data'];
    }

    /**
     * 配送方式&计价
     */
    public static function  price($storeId, $uniacid, $platform_order_id)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $app = self::storeApp($storeId, $uniacid);
        $data = [
            'third_partner_id' => $app->getMerchant()->getThirdPartnerId(),
        ];
        $res =  $app->getClient()->postJson("/OpenApi/deliver/price/{$platform_order_id}", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        return $res['data'];
    }


    /**
     * 发起配送
     */
    public static function  deliver($storeId, $uniacid, $platform_order_id, array $ship_ways = [])
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $app = self::storeApp($storeId, $uniacid);
        $data = [
            'third_partner_id' => $app->getMerchant()->getThirdPartnerId(),
            'ship_ways' => $ship_ways
        ];
        $res =  $app->getClient()->postJson("/OpenApi/deliver/create/{$platform_order_id}", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        return true;
    }

    /**
     * 发起配送
     */
    public static function  deliverCancel($storeId, $uniacid, $delivery_uuid)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $app = self::storeApp($storeId, $uniacid);
        $data = [
            'third_partner_id' => $app->getMerchant()->getThirdPartnerId(),
        ];
        $res =  $app->getClient()->postJson("/OpenApi/deliver/cancel/{$delivery_uuid}", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        return $res['data']['deduct_fee'];
    }

    /**
     * 二维码充值
     */
    public static function  qrCharge($storeId, $uniacid, $money, $type = 1)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $model = Channel::where('uniacid', $uniacid)->where('storeId', $storeId)->where('type', 3)->first();
        if (empty($model)) {
            throw new BadRequestException('请先授权开通门店');
        }
        $app = self::storeApp($storeId, $uniacid);
        $data = [
            'third_partner_id' => $app->getMerchant()->getThirdPartnerId(),
            'store_id' => $app->getMerchant()->getMerchantId(),
            'pay_way' => $type,
            'amount' => bcmul($money, 100)
        ];
        $res =  $app->getClient()->postJson("/OpenApi/fund/qr_charge", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        return $res['data'];
    }

    /**
     * 充值查询
     */
    public static function  chargeQuery($storeId, $uniacid, $charge_id)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $app = self::app($storeId, $uniacid);
        $data = [
            'third_partner_id' => $app->getMerchant()->getThirdPartnerId(),
            'charge_id' => $charge_id
        ];
        $res =  $app->getClient()->postJson("/OpenApi/fund/charge_query", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        return  $res['data'];
    }

    /**
     * 余额查询
     */
    public static function  balance($storeId, $uniacid)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $app = self::storeApp($storeId, $uniacid);
        $data = [
            'third_partner_id' => $app->getMerchant()->getThirdPartnerId(),
            'store_id' => $app->getMerchant()->getMerchantId()
        ];
        $res =  $app->getClient()->postJson("/OpenApi/fund/balance", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        return  $res['data']['amount'];
    }


    public static function  statistic($storeId, $uniacid)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $app = self::storeApp($storeId, $uniacid);
        $data = [
            'third_partner_id' => $app->getMerchant()->getThirdPartnerId(),
            'store_id' => $app->getMerchant()->getMerchantId()
        ];
        $res =  $app->getClient()->postJson("/OpenApi/deliver/fund/balance", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        return  $res['data']['amount'];
    }

    public static function  mockCallback($storeId, $uniacid, $uuid, $state)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $app = self::storeApp($storeId, $uniacid);
        $data = [
            'delivery_uuid' => $uuid,
            'state' => $state
        ];
        $res =  $app->getClient()->postJson("/OpenApi/store/detail/" . $app->getMerchant()->getMerchantId(), $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        return  $res['data']['amount'];
    }


    public static function  storeDetail($storeId, $uniacid)
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new BadRequestHttpException('门店不存在');
        }
        $app = self::storeApp($storeId, $uniacid);
        $data = [
            'delivery_uuid' => $uuid,
            'state' => $state
        ];
        $res =  $app->getClient()->postJson("/OpenApi/deliver/mockCallback", $data)->toArray();
        if ($res['code'] != 0) {
            throw new BadRequestHttpException($res['msg']);
        }
        return  $res['data']['amount'];
    }
}
