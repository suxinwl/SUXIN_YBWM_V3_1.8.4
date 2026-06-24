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
use App\Models\EquityCard\Card;
use App\Models\Member\Vip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class EquityCardController extends ApiController
{

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $this->userId();
        $list = Card::with([
            'order' => function ($q) use ($userId) {
                return $q->where('userId', $userId)->where('startTime', '<=', Carbon::now()->toDayDateTimeString())
                    ->where('endTime', ">=", Carbon::now()->toDayDateTimeString());
            }
        ])->where("uniacid", $this->uniacid())
            ->where('state', 1)
            ->get();
        if ($list) {
            $list = collect($list)->map(function ($card) {
                $card->setAppends(['isBuy', 'couponGive', 'periodCouponGive']);
                return $card;
            });
        }
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $userId = $this->userId();
        $model = Card::with([
            'order' => function ($q) use ($userId) {
                return $q->where('userId', $userId);
            }
        ])->where("uniacid", $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        if ($model) {
            $model->setAppends(['isBuy', 'couponGive', 'periodCouponGive']);
        }
        return $this->success($model);
    }
}
