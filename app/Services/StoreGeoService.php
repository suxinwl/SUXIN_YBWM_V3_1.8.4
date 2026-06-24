<?php

namespace App\Services;

use App\Models\ChannelConfig;
use App\Models\TopLevel;
use App\Traits\ResourceTrait;
use App\Models\Config;
use App\Models\OpenWechatAuth;
use App\Models\Store;
use App\Models\StoreConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StoreGeoService
{
    public static function getKey($uniacid = 0)
    {
        return "storeGeo:" . $uniacid;
    }

    public static function extists($uniacid = 0)
    {
        return  Redis::exists(self::getKey($uniacid));
    }

    public static function getStoreGeo($uniacid = 0)
    {
        Redis::delete(self::getKey($uniacid));
        $list = DB::table('store')->select(["id", 'lng', 'lat'])
            ->whereNull('deleted_at')
            ->where('uniacid', $uniacid)->get();
        foreach ($list as $key => $v) {
            Redis::geoAdd(self::getKey($uniacid), $v->lng, $v->lat, $v->id);
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

    public static function getGeoList($uniacid,$lat,$lng)
    {
        $key = self::getKey($uniacid);
        self::getStoreGeo($uniacid);
        return Redis::geoRadius($key,$lng,$lat,50000,'km',['WITHDIST']);
    }
}
