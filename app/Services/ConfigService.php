<?php
namespace App\Services;
use App\Models\ChannelConfig;
use App\Traits\ResourceTrait;
use App\Models\Config;
use App\Models\OpenWechatAuth;
use App\Models\StoreConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Models\LuckyWheel;
class ConfigService
{
    use ResourceTrait;

    //获取系统设置
    public static function getSystemSet($ident)
    {
        if (empty($ident)) {
            return [];
        }
        $data = Config::getSystemSet($ident);
        return $data;
    }

    /**
     * 获取业务平台配置
     */
    public static function getChannelConfig($ident, $uniacid)
    {

        if (empty($ident)) {
            return [];
        }
        $res = ChannelConfig::where("uniacid", $uniacid)->where('ident', $ident)->where('storeId', 0)->first();
        if ($res) {
            $data =json_decode($res,true);
            $data=$data['data'];
        } else {
            $data = [];
        }
        return $data;
    }

    /**
     * 获取业务平台配置
     */
    public static function setChannelConfig($ident, $uniacid, $note = '')
    {
        if (empty($ident)) {
            return [];
        }
        $res = ChannelConfig::where(['ident' => $ident, 'uniacid' => $uniacid])->where('storeId', 0)->first();
        if ($res) {
            $data = $res->data ?: (object)[];
            $data->ident = $ident;
            $data->uniacid = $res->uniacid;
            $data->identName = $res->name;
        } else {
            $data = [];
        }
        return $data;
    }

    public static function getStoreConfig($ident, $storeId)
    {
        if (empty($ident)) {
            return [];
        }
        $res = StoreConfig::where(['ident' => $ident, 'storeId' => $storeId])->orderBy('id', 'desc')->first();
        if ($res) {
            $data =  json_decode($res,true);
            $data=$data['data'];
        } else {
            $data = [];
        }
        return $data;
    }


    public static function setStoreConfig($ident, $uniacid, $storeId, $note = '')
    {
        if (empty($ident)) {
            return [];
        }
        $res = StoreConfig::where(['ident' => $ident, 'uniacid' => $uniacid, 'storeId' => $storeId])->orderBy('id', 'desc')->first();
        if ($res) {
            $data = $res->data ?: (object)[];
            $data->ident = $ident;
            $data->uniacid = $res->uniacid;
            $data->identName = $res->name;
        } else {
            $data = [];
        }
        return $data;
    }

    public static  function miniConfig($uniacid)
    {
        $openConfig = OpenWechatAuth::where('uniacid', $uniacid)->where('type', 'mini')->orderBy('id', 'desc')->first();
        $miniConfig =  ConfigService::getChannelConfig('miniConfig', $uniacid);
        $type = 0;
        $data = null;
        if ($openConfig) {
            $type = 1;
            $data = $openConfig;
        }
        if ($miniConfig) {
            $type = 2;
            $data = $miniConfig;
        }
        return ['type' => $type, 'data' => $data];
    }

    public static function officialConfig($uniacid)
    {
        $openConfig = OpenWechatAuth::where('uniacid', $uniacid)->where('type', 'official')->orderBy('id', 'desc')->first();
        $miniConfig =  ConfigService::getChannelConfig('officialConfig', $uniacid);
        $type = 0;
        $data = null;
        if ($openConfig) {
            $type = 1;
            $data = $openConfig;
        }
        if ($miniConfig) {
            $type = 2;
            $data = $miniConfig;
        }
        return ['type' => $type, 'data' => $data];
    }

    public static function officialSwitch($uniacid)
    {
        $data = self::officialConfig($uniacid);
        if ($data['type'] == 1) {
            OpenWechatAuth::where('uniacid', $uniacid)->where('type', 'official')->delete();
        }
        if ($data['type'] == 2) {
            ChannelConfig::where('uniacid', $uniacid)->where('ident', 'officialConfig')->delete();
        }
        return true;
    }

    public static function miniSwitch($uniacid)
    {
        $data = self::miniConfig($uniacid);
        if ($data['type'] == 1) {
            OpenWechatAuth::where('uniacid', $uniacid)->where('type', 'mini')->delete();
        }
        if ($data['type'] == 2) {
            ChannelConfig::where('uniacid', $uniacid)->where('ident', 'miniConfig')->delete();
        }
        return true;
    }


    public static function getChannelConfigFormMap($uniacid, $ident, $storeId = 0)
    {
        if($ident == "luckyWheel"){
            return LuckyWheel::where('uniacid', $uniacid)->first();
        }
        $key =  "configMap:" . $uniacid . 'storeId:' . $storeId;
        if (Cache::has($key)) {
            $config  =  json_decode(Cache::get($key), true);
        } else {
            $config = ChannelConfig::where("uniacid", $uniacid)->where('storeId', 0)->get();
            $config = collect($config)->mapWithKeys(function ($item, $key) {
                $item  = collect($item)->toArray();
                return [$item['ident'] => collect($item['data'])->toArray()];
            });
            Cache::set($key, json_encode($config, 320));
        }
        foreach (explode('.', $ident) as $segment) {
            if (collect($config)->has($segment)) {
                $config =  $config[$segment];
            } else {
                return  null;
            }
        }
        return $config;
    }


    public static function getSystemConfigFormMap($ident)
    {
        $key =  "sysConfigMap:";
        if (Cache::has($key)) {
            $config  =  json_decode(Cache::get($key), true);
        } else {
            $config = Config::get();
            $config = collect($config)->mapWithKeys(function ($item, $key) {
                $item  = collect($item)->toArray();
                return [$item['ident'] =>  collect($item['data'])->toArray()];
            });
            Cache::set($key, json_encode($config, 320));
        }
        foreach (explode('.', $ident) as $segment) {
            if (collect($config)->has($segment)) {
                $config =  $config[$segment];
            } else {
                return  null;
            }
        }
        return $config;
    }


    public static function getStoreConfigFormMap($storeId, $ident)
    {
        $key =  "storeConfigMap:" . $storeId;
        if (Cache::has($key)) {
            $config  =  json_decode(Cache::get($key), true);
        } else {
            $config = StoreConfig::where("storeId", $storeId)->get();
            $config = collect($config)->mapWithKeys(function ($item, $key) {
                $item  = collect($item)->toArray();
                return [$item['ident'] => collect($item['data'])->toArray()];
            });
            Cache::set($key, json_encode($config, 320));
        }
        foreach (explode('.', $ident) as $segment) {
            if (collect($config)->has($segment)) {
                $config =  $config[$segment];
            } else {
                return  null;
            }
        }
        return $config;
    }
}
