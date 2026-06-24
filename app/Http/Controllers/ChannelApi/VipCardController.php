<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\ConfigRequest;
use App\Models\ChannelConfig as Config;
use App\Models\ChannelConfig;
use App\Models\OpenWechatAuth;
use App\Services\ChannelConfigService;
use App\Services\ConfigService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Ali;
use App\Models\Aliauth;
use App\Models\ShopAccount;
use App\Models\Admin\Apply;
use App\Models\Collect;
use App\Models\Drag;
use App\Models\Member\Vip;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class VipCardController extends ApiController
{

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Vip::where("uniacid", $this->uniacid())
            ->where('storeId', $this->storeId())
            ->orderBy('level', 'asc')
            ->get();
        $list = collect($list)->map(function ($vip, $key) {
            $vip->setAppends(['couponList','power','extPowerData']);
            return $vip;
        });
        return $this->success($list);
    }
}
