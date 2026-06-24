<?php

namespace App\Services;

use App\Traits\ResourceTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SwooleJobService
{

    public static function check()
    {
        $url = 'http://127.0.0.1:' . config('laravels.listen_port') . '/api/login';
        try {
            $res = Http::timeout(1)->post($url, []);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function job($className, $data)
    {
        if (!self::check()) {
            return false;
        }
        $url = 'http://127.0.0.1:' . config('laravels.listen_port') . '/common/swooleJob';
        $res =  Http::timeout(1)->post($url, ['className' => $className, 'jobData' => $data]);
        Log::error($res);
    }

    public static function sendMessage($uniacid = 0, $storeId = 0, $message = '')
    {
        if (!self::check()) {
            return false;
        }
        $url = 'http://127.0.0.1:' . config('laravels.listen_port') . '/common/swooleJob/sendMessage';
        return Http::timeout(1)->post($url, ['uniacid' => $uniacid, 'storeId' => $storeId, 'message' => $message]);
    }
}
