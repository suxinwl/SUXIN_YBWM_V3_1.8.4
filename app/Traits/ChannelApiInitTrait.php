<?php

namespace App\Traits;

use App\Enums\SceneEnum;
use App\Models\Store;
use Cache;
use Illuminate\Support\Facades\DB;

trait ChannelApiInitTrait
{
    public function user()
    {
        return auth('user')->user();
    }


    public function userId()
    {
        return $this->user() ?  $this->user()->id : 0;
    }

    public function scene()
    {
        return Request()->header('scene', Request()->scene) ?: SceneEnum::SCENE_TAKEOUT;
    }

    public function storeId()
    {
        return  Request()->header('storeid', false) ?: Request()->storeId ?: 0;
    }

    public function isolate()
    {
        $key = "storeIsolate:" . $this->storeId();
        if (!Cache::has($key)) {
            Cache::set($key, 0);
            if ($this->storeId()) {
                $res = DB::table('store')->select('isolate')->where('id', $this->storeId())->first();
                Cache::set($key, intval($res->isolate));
            }
        }
        return Cache::get($key);
    }

    public function tableId()
    {
        return  Request()->header('tableid', false) ?: Request()->tableId ?: 0;
    }

    public function diningType()
    {
        return  Request()->diningType ?: 0;
    }

    public function uniacid()
    {
        return Request()->header('uniacid');
    }
    public function timeArr()
    {
        $startTime = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
        $endTime = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1);
        return [
            1 => [ //今天
                'startTime' => $startTime,
                'endTime' => $endTime,
            ],
            2 => [ //自定义
                'startTime' => Request()->startTime,
                'endTime' => Request()->endTime,
            ],
            3 => [ //昨日
                'startTime' => date("Y-m-d 00:00:00", strtotime("-1 day")),
                'endTime' => date("Y-m-d 23:59:59", strtotime("-1 day")),
            ],
            4 => [ //7天
                'startTime' => date('Y-m-d 00:00:00', strtotime("-7 day")),
                'endTime' => date('Y-m-d 23:59:59', strtotime('-1 day')),
            ],
            5 => [ //15天
                'startTime' => date('Y-m-d 00:00:00', strtotime("-15 day")),
                'endTime' => date('Y-m-d 23:59:59', strtotime('-1 day')),
            ],
            6 => [ //30天
                'startTime' => date('Y-m-d 00:00:00', strtotime("-30 day")),
                'endTime' => date('Y-m-d 23:59:59', strtotime('-1 day')),
            ]
        ];
    }

    public function appType()
    {
        $appType = Request()->header('appType', 'mini');
        return appType($appType);
    }

    public function channel()
    {
        $appType = Request()->header('appType', 'mini');
        return $appType;
    }
    public function isolateStore()
    {
        return $this->isolate() == 1 ? $this->storeId() : 0;
    }
}
