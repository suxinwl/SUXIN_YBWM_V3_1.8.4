<?php

namespace App\Services;

use App\Models\Coupon\Activity;
use App\Models\ExchangeCode\ExchangeCode;
use App\Models\ExchangeCode\ExchangeCodeReceive;
use App\Models\Mini\ApplyMiniPath;
use App\Models\Mini\MiniPath;
use App\Models\ShortLink;
use App\Models\Sms;
use App\Models\SmsLog;
use App\Models\Store;
use App\Models\Tables\Table;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Overtrue\EasySms\EasySms;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ShortLinkService extends BaseService
{
    public static function createTableLink(Table $table)
    {
        return ShortLink::create([
            'uniacid' => $table->uniacid,
            'storeId' => $table->storeId,
            'type' => 'table',
            'ident' => $table->id,
            'shortLink' => GetRandStr(12),
            'wx' => [
                'type' => 'mini',
                'path' => '/pages/index/index',
                'query' => "uniacid={$table->uniacid}&storeId={$table->storeId}&tableId={$table->id}",
                'scene' => "{$table->id}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }


    public static function createPayLink(Store $store)
    {
        return ShortLink::create([
            'uniacid' => $store->uniacid,
            'storeId' => $store->id,
            'type' => 'personPay',
            'ident' => 0,
            'shortLink' => GetRandStr(13),
            'wx' => [
                'type' => 'mini',
                'path' => '/pages/shop/in/dmf',
                'query' => "storeId={$store->id}&isolate={$store->isolate}",
                'scene' => "{$store->id}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }


    public static function createGoods(Store $store)
    {
        return ShortLink::create([
            'uniacid' => $store->uniacid,
            'storeId' => $store->id,
            'type' => 'storeGoods',
            'ident' => 0,
            'shortLink' => GetRandStr(10),
            'wx' => [
                'type' => 'mini',
                'path' => '/pages/index/goods',
                'query' => "storeId={$store->id}&isolate={$store->isolate}",
                'scene' => "{$store->id}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }
    public static function fastfood(Store $store)
    {
        return ShortLink::create([
            'uniacid' => $store->uniacid,
            'storeId' => $store->id,
            'type' => 'fastfood',
            'ident' => 0,
            'shortLink' => GetRandStr(10),
            'wx' => [
                'type' => 'mini',
                'path' => '/pages/index/goods',
                'query' => "storeId={$store->id}&isolate={$store->isolate}&diningType=6",
                'scene' => "{$store->id}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }

    public static function takeScreen(Store $store)
    {
        return ShortLink::create([
            'uniacid' => $store->uniacid,
            'storeId' => $store->id,
            'type' => 'takeScreen',
            'ident' => 0,
            'shortLink' => GetRandStr(10),
            'wx' => [
                'type' => 'mini',
                'path' => '/pages/index/goods',
                'query' => "storeId={$store->id}&isolate={$store->isolate}&diningType=6",
                'scene' => "{$store->id}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }

    public static function createExchange(ExchangeCodeReceive $exChange)
    {
        return ShortLink::create([
            'uniacid' => $exChange->uniacid,
            'storeId' => 0,
            'type' => 'exChange',
            'ident' => $exChange->sn,
            'shortLink' => GetRandStr(12),
            'wx' => [
                'type' => 'mini',
                'path' => '/pages/other/coupon/dhm',
                'query' => "code={$exChange->sn}&storeId=$exChange->storeId",
                'scene' => "{$exChange->sn}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }
    public static function createCouponActivity(Activity $activity)
    {
        return ShortLink::create([
            'uniacid' => $activity->uniacid,
            'storeId' => 0,
            'type' => 'couponActivity',
            'ident' => $activity->id,
            'shortLink' => GetRandStr(12),
            'wx' => [
                'type' => 'mini',
                'path' => '/pages/other/coupon/dhm',
                'query' => "couponId={$activity->id}&storeId={$activity->storeId}",
                'scene' => "{$activity->id}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }

    public static function createUniacidLink(MiniPath $model, $uniacid = 0)
    {
        return ShortLink::create([
            'uniacid' => $uniacid,
            'storeId' => 0,
            'type' => $model->type,
            'ident' => 0,
            'shortLink' => GetRandStr(13),
            'wx' => [
                'type' => 'mini',
                'path' => '/' . $model->path,
                'query' => "uniacid={$uniacid}",
                'scene' => "{$uniacid}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }

    public static function queuingUp(Store $store)
    {
        return ShortLink::create([
            'uniacid' => $store->uniacid,
            'storeId' => $store->id,
            'type' => 'queuingUp',
            'ident' => 0,
            'shortLink' => GetRandStr(10),
            'wx' => [
                'type' => 'mini',
                'path' => '/pages/index/goods',
                'query' => "storeId={$store->id}&isolate={$store->isolate}",
                'scene' => "{$store->id}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }

    public static function storeWifi(Store $store)
    {
        return ShortLink::create([
            'uniacid' => $store->uniacid,
            'storeId' => $store->id,
            'type' => 'storeWifi',
            'ident' => 0,
            'shortLink' => GetRandStr(10),
            'wx' => [
                'type' => 'mini',
                'path' => '/pages/other/wifi',
                'query' => "storeId={$store->id}&isolate={$store->isolate}",
                'scene' => "{$store->id}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }

    public static function storeIndex(Store $store)
    {
        return ShortLink::create([
            'uniacid' => $store->uniacid,
            'storeId' => $store->id,
            'type' => 'storeWifi',
            'ident' => 0,
            'shortLink' => GetRandStr(10),
            'wx' => [
                'type' => 'mini',
                'path' => '/pages/index/index',
                'query' => "storeId={$store->id}&isolate={$store->isolate}",
                'scene' => "{$store->id}"
            ],
            'ali' => [
                'type' => 'text',
                'text' => '尽请期待',
            ]
        ]);
    }
}
