<?php
namespace App\Services;

use App\Models\ChannelConfig;
use App\Traits\ResourceTrait;
use App\Models\Config;
class ChannelConfigService{
    use ResourceTrait;

    //获取系统设置
    public static function getSystemSet($ident,$uniacid)
    {
        if(empty($ident)|| empty($uniacid)){
            return [];
        }
        return ChannelConfig::where('ident',$ident)->first();
    }
}