<?php

namespace App\Services;

use App\Models\ChannelConfig;
use App\Models\TopLevel;
use App\Traits\ResourceTrait;
use App\Models\Config;
use App\Models\Member\Address;
use App\Models\OpenWechatAuth;
use App\Models\Store;
use App\Models\StoreConfig;
use Illuminate\Support\Facades\Redis;

class AddressGeoService
{
    public static function getKey($uniacid = 0, $userId = 0)
    {
        return "AddressGeo:" . $uniacid . $userId;
    }

    public static function extists($uniacid = 0)
    {
        return  Redis::exists(self::getKey($uniacid));
    }

    public static function getStoreGeo($uniacid = 0, $userId)
    {
        $has =  self::extists($uniacid);
        if (!$has) {
            $list = Address::where('uniacid', $uniacid)->where('userId', $userId)->get();
            foreach ($list as $key => $v) {
                Redis::geoAdd(self::getKey($uniacid), $v->lng, $v->lat, $v->id);
            }
        }
        return true;
    }

    /**
     * 获取两个点的距离
     */
    public static function getDistance($uniacid, $start, $end)
    {
        $key = self::getKey($uniacid);
        self::getStoreGeo($uniacid);
        return  Redis::geodist($key, $start, $end);
    }


    /**
     * 获取坐标点为圆心 $km 范围内的门店
     */
    public static function getRadius($uniacid, $lat, $lng, $km, $unit = 'km', $options = ["ASC"])
    {
        $key = self::getKey($uniacid);
        self::getStoreGeo($uniacid);
        return  Redis::geoRadius($key, $lng, $lat, $km, $unit, $options);
    }
}
