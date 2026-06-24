<?php

namespace App\Traits;

use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

trait ChannelInitTrait
{
    public function user()
    {
        return auth('admin')->user();
    }

    public function userId()
    {
        return $this->user() ?  $this->user()->id : 0;
    }

    public function uniacid()
    {
        return Request()->header('uniacid');
    }

    public function appType()
    {
        $appType = Request()->header('appType', 'shoudong');
        return appType($appType);
    }
    public function tableId()
    {
        return  Request()->header('tableid', false) ?: Request()->tableId ?: 0;
    }

    public function diningType()
    {
        return  Request()->diningType ?: 0;
    }

    public function storeId()
    {
        $storeId =  Request()->header('storeid', false) ?: Request()->storeId ?: 0;
        if ($this->user()->isAdmin == 0) {
            $storeIds = $this->user()->storeId;
            if (!in_array($storeId, $storeIds) && $storeId) {
                throw new BadRequestException('您无权管理该门店');
            }
        }
        return $storeId;
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

    public function isolateStore()
    {
        return $this->isolate() == 1 ? $this->storeId() : 0;
    }
}
