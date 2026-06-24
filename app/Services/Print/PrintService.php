<?php

namespace App\Services\Print;

use App\Models\Print\Feie\FeiePrint;
use App\Models\ShopPrint;
use App\Services\ConfigService;

class PrintService
{

    public static function bindPrint($type, $config = [])
    {
        switch ($type) {
            case 1:
                return FeiePrint::bindPrint(['user' => $config['appid'], 'ukey' => $config['secret'], 'sn' => $config['sn'], 'keys' => $config['keys']]);
                break;
            default:
                return false;
        }
    }

    public static function destroy($type, $config = [])
    {
        switch ($type) {
            case 1:
                return FeiePrint::destroy(['user' => $config['appid'], 'ukey' => $config['secret'], 'sn' => $config['sn'], 'keys' => $config['keys']]);
                break;
            default:
                return false;
        }
    }

    public static function print($orderId, $uniacid, $shopId)
    {
        $printList = ShopPrint::where('state', 1)->where('shopId', $shopId)->where('uniacid', $uniacid)->get();
        foreach ($printList as $key => $print) {
            $config = collect($print)->toArray();
            switch ($print->type) {
                case 1:
                    return FeiePrint::print($orderId, ['user' => $config['appid'], 'ukey' => $config['secret'], 'sn' => $config['sn'], 'keys' => $config['keys']]);
                    break;
                default:
                    return false;
            }
        }
    }
}
