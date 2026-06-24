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
use App\Models\EquityCard\Member;
use App\Models\EquityCard\Order;
use App\Models\Member\Vip;
use App\Models\Order\OrderGoods;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EquityCardOrderController extends ApiController
{

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Order::where("uniacid", $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->with(['equityCard' => function ($q) {
                return $q->select([
                    'id', 'name', 'desc', 'imageType', 'image', 'textColor', 'themeColor', 'day'
                ]);
            }])
            ->where('userId', $this->userId())
            ->where('state', '>', 1)
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $list = Order::where("uniacid", $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->with(['equityCard' => function ($q) {
                return $q->select([
                    'id', 'name', 'desc', 'imageType', 'image', 'textColor', 'themeColor', 'day'
                ]);
            }])
            ->where('userId', $this->userId())
            ->find($id);
        if (!$list) {
            return $this->failed('数据不存在');
        }
        return $this->success($list);
    }

    public function myCard(Request $request)
    {

        $userId  = $this->userId();
        $list = Member::where("uniacid", $this->uniacid())
            ->with(['equityCard' => function ($q) {
                return $q->select([
                    'id', 'name', 'desc', 'imageType', 'image', 'textColor', 'themeColor', 'day',
                ]);
            }])
            ->withCount(['goods' => function ($q) use ($userId) {
                return $q->where('userId', $userId);
            }])
            ->where('userId', $this->userId())
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == 'start') {
                    return $q->where('endTime', ">", Carbon::now()->toDateTimeString());
                } elseif ($request->state == 'end') {
                    return $q->where('endTime', "<", Carbon::now()->toDateTimeString());
                }
                return $q;
            })
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $userId = $this->userId();
        $id = $request->equityCardId;
        $model = Card::with([
            'order' => function ($q) use ($userId) {
                return $q->where('userId', $userId)->where('state', ">", 1);
            }
        ])->where("uniacid", $this->uniacid())
            ->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        if ($model) {
            $model->setAppends(['isBuy']);
        }
        if (!$model->isBuy) {
            return $this->failed('当前无法购买');
        }
        $order = Order::create([
            'orderSn' => getTakeOutNo(),
            'uniacid' => $this->uniacid(),
            'storeId' => $this->storeId(),
            'userId' => $this->userId(),
            'state' => 1,
            'equityCardId' => $model->id,
            'score' => $this->appType(),
            'money' => $model->money,
            'sellMoney' => $model->money,
        ]);
        return $this->success($order);
    }
}
