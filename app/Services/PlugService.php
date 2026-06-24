<?php

namespace App\Services;

use App\Models\ApplyPlugs;
use App\Models\Plug;
use App\Traits\ResourceTrait;

class PlugService
{
    public static function plugAdd($name, $type = 'channel', $base = [])
    {
        $model = Plug::where('appName', $name)->where('appType', $type)->first();
        if (empty($model)) {
            $model = new Plug();
            $model['name'] = '';
            $model['logo'] = '';
            $model['desc'] = '';
            $model['infoSwitch'] = 0;
            $model['paySwitch'] = 0;
            $model['foreverSwitch'] = 0;
            $model['payData'] = '';
            $model['status'] = 1;
            $model['payType'] = 0;
            $model['appType'] = $type;
            $model['appName'] = $name;
            $model['baseName'] = $base['baseName'] ?: '';
            $model['baseLogo'] = $base['baseLogo'] ?: '';
            $model['baseDesc'] = $base['baseDesc'] ?: '';
            $model['sort'] = $base['sort'] ?: 0;
        } else {
            $model['appType'] = $type;
            $model['appName'] = $name;
            $model['baseName'] = $base['baseName'] ?: '';
            $model['baseLogo'] = $base['baseLogo'] ?: '';
            $model['baseDesc'] = $base['baseDesc'] ?: '';
            $model['sort'] = $base['sort'] ?: 0;
        }
        return $model->save();
    }

    public static function PlugDel($name)
    {
        $model = Plug::where('appName', $name)->first();
        if ($model) {
            $model->forceDelete();
        }
        return true;
    }

    public static function typeFormat($type)
    {
        $data = [
            4 => "channel",
            1 => "plug",
            0 => "service"
        ];
        return isset($data[$type]) ? $data[$type] : null;
    }

    public static function applyPlug($uniacid)
    {
        $applyPlugs = ApplyPlugs::with('plug')->whereHas('plug', function ($q) {
            return $q->where('status', 1);
        })->where('state', 1)->where('uniacid', $uniacid)->get();
        return collect($applyPlugs)->pluck('plug')->pluck('appName')->all();
    }
}
