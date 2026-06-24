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

class CouponPackController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->isolateStore();
        $isolate = $this->isolate();
        $list = CouponPack::where("uniacid", $this->uniacid())
            ->where("startTime", '<=', Carbon::now()->toDateTimeString())
            ->where("endTime", '>=', Carbon::now()->toDateTimeString())
            ->where('state', 1)
            ->when($storeId, function ($q) use ($storeId, $uniacid, $isolate) {
                return $q->where(function ($q) use ($storeId, $uniacid, $isolate) {
                    return $q->whereHas('stores', function ($q) use ($storeId, $uniacid) {
                        return $q->where(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', $storeId)->where('type', 2);
                        })->orWhere(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', '!=', $storeId)->where('type', 3);
                        });
                    })->when($isolate == 0, function ($q) use ($isolate, $uniacid, $storeId) {
                        return $q->orWhere(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeType', 1)
                                ->where('uniacid', $uniacid)
                                ->where('storeId', $storeId);
                        });
                    });;
                });
            })
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId();
        $model = CouponPack::where("uniacid", $this->uniacid())
            ->where("startTime", '<=', Carbon::now()->toDateTimeString())
            ->where("endTime", '>=', Carbon::now()->toDateTimeString())
            ->where('state', 1)
            ->where('id', $id)
            ->when($storeId, function ($q) use ($storeId, $uniacid) {
                return $q->where(function ($q) use ($storeId, $uniacid) {
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
        $model->setAppends(['countdown', 'couponList']);
        return $this->success($model);
    }
}
