<?php

namespace App\Http\Controllers\Channel\Delivery;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Admin\Apply;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Channel;
use App\Models\Wechat\Maiyatian\Application;
use App\ServicesWaiSongBangController\Delivery\MaiyatianService;
use App\Services\Delivery\WaisongBangService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class WaiSongBangController extends ApiController
{


    public function  store(Request $request)
    {
        $apply =  Apply::find($this->uniacid());
        if (!$apply) {
            return $this->failed('数据不存在');
        }
        $key = "applyWaiSongBangId" . $this->uniacid() . $this->storeId();
        $list = Cache::get($key, false);
        if (empty($list)) {
            $data = WaisongBangService::createStore($this->storeId());
            cache::set($key, $data);
        }

        return $this->success(cache::get($key));
    }

    /**
     * 开通配送渠道
     */
    public function deliverShop(Request $request, $shipWay)
    {
        $res = WaisongBangService::createDeliverShop($this->storeId(), $this->uniacid(), $shipWay);
        return $this->success($res);
    }

    /**
     * 开通配送渠道
     */
    public function balance(Request $request)
    {
        $res = WaisongBangService::balance($this->storeId(), $this->uniacid());
        return $this->success($res);
    }

    /**
     * 
     */
    public function deliverShopQuery(Request $request, $shipWay)
    {
        $res = WaisongBangService::deliverShopState($this->storeId(), $this->uniacid(), $shipWay);
        return $this->success($res);
    }

    /**
     * 充值
     */
    public function charge(Request $request)
    {
        $res = WaisongBangService::qrCharge($this->storeId(), $this->uniacid(), $request->money, $request->type);
        $res['result'] = 'data:image/png;base64,' . base64_encode(QrCode::format('png')->size(400)->generate($res['result']));
        return $this->success($res);
    }

    /**
     * 充值查询
     */
    public function chargeState(Request $request, $chargeId)
    {
        $res = WaisongBangService::chargeQuery($this->storeId(), $this->uniacid(), $chargeId);
        return $this->success($res);
    }
}
