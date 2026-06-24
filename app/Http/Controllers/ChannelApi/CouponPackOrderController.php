<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\CouponPack\CouponPack;
use App\Models\CouponPack\Order;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\UserAccount;
use App\Services\MenuService;
use App\Services\UserService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CouponPackOrderController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId();
        $list = Order::where("uniacid", $this->uniacid())
            ->where('state', 6)
            ->where('userId', $this->userId())
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId();
        $model = Order::where("uniacid", $this->uniacid())
            ->where('state', 6)
            ->where('id', $id)
            ->where('userId', $this->userId())
            ->first();
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        return $this->success($model);
    }

    public function store(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId();
        $model = CouponPack::where("uniacid", $this->uniacid())
            ->where("startTime", '<=', Carbon::now()->toDateTimeString())
            ->where("endTime", '>=', Carbon::now()->toDateTimeString())
            ->where('state', 1)
            ->where('id', $request->couponPackId)
            ->where(function ($q) use ($storeId, $uniacid) {
                $q->where(function ($q) use ($storeId, $uniacid) {
                    return $q->whereHas('stores', function ($q) use ($storeId, $uniacid) {
                        return $q->where(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', $storeId)->where('type', 2);
                        })->orWhere(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', '!=', $storeId)->where('type', 3);
                        });
                    })->orWhere(function ($q) use ($storeId, $uniacid) {
                        return $q->where('storeType', 1);
                    });
                });
            })->first();
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        if ($model->inventory <= 0) {
            return $this->failed('库存不足');
        }
        $limitKey = "couponPackOrder:userlimit:{$model->id}:{$this->userId()}";
        $userlimit = Cache::get($limitKey, 0);
        if ($model->rule['userLimitSwitch'] == 0 && $userlimit > $model->rule['userLimit']) {
            throw new BadRequestException('购买数量已达上限');
        }
        $dayLimitKey = "couponPackOrder:userDaylimit:{$model->id}:" . date("Ymd") . ":{$this->userId()}";
        $dayLimit = Cache::get($dayLimitKey, 0);
        if ($model->rule['userDaySwitch'] == 0 && $dayLimit > $model->rule['userDaylimit']) {
            throw new BadRequestException('今日购买数量已达上限');
        }
        $day=date('w');
        if($model->weekArr){
            if(!in_array($day,$model->weekArr)){
                throw new BadRequestException('不在购买时间段');
            }
        }

        $order = Order::create([
            'uniacid' => $uniacid,
            'storeId' => $storeId,
            'userId' => $this->userId(),
            'couponPackId' => $model->id,
            'score' => $this->appType(),
            'money' => $model->price,
            'orderSn' => getTakeOutNo(),
            'sellMoney' => $model->sellPrice,
            'couponGive' => $model->couponGive
        ]);
        return $this->success($order->orderSn);
    }
}
