<?php

namespace App\Services;

use App\Traits\ResourceTrait;
use Spatie\FlareClient\Http\Exceptions\BadResponseCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class MapService
{

    public static function region($lat, $lng, $uniacid, $throw = true)
    {
        $config = ConfigService::getChannelConfig('basicSetting', $uniacid);
        $type = $config['ismap'] ?? 1;
        if ($type  == 1) {
            return self::txRegion($lat, $lng, $config['txKey'], $throw);
        }
        if ($type  == 2) {
            return self::gdRegion($lat, $lng, $config['gdKey'], $throw);
        }
        return false;
    }

    public static function txRegion($lat, $lng, $key, $throw = true)
    {

        $region = HttpRequest("https://apis.map.qq.com/ws/geocoder/v1/", [
            'location' => "$lat,$lng",
            'key' => $key ?? 'FUTBZ-4PECF-SVDJC-N74XF-OGIVQ-GDBVK'
        ]);
        if ($region['status'] != 0 && $throw) {
            throw new BadRequestException($region['message']);
        }
        $code = $region['result']['ad_info']['adcode'];
        $address =  $region['result']['address'];
        $formatted_addresses = $region['result']['address'];
        return [
            'code' => $code,
            'address' => $address,
            'formatted_addresses' => $formatted_addresses
        ];
    }

    public static function gdRegion($lat, $lng, $key, $throw = true)
    {

        $region  = HttpRequest("https://restapi.amap.com/v3/geocode/regeo", [
            'location' => "$lng,$lat",
            'key' => $config['gdKey'] ?? 'e7bb89dfb82756cdf11239b43bb6dc2a'
        ], [], 'get');
        if ($region['status'] != 1 && $throw) {
            throw new BadRequestException($region['info']);
        }
        $code = $region['regeocode']['addressComponent']['adcode'];
        $address = $region['regeocode']['formatted_address'];
        $formatted_addresses = $region['regeocode']['formatted_address'];
        return [
            'code' => $code,
            'address' => $address,
            'formatted_addresses' => $formatted_addresses
        ];
    }
}
