<?php

namespace App\Http\Controllers\ChannelApi;

use App\Models\WindowCoupon\Coupon as WindowCouponCoupon;
use App\Models\WindowCoupon\CouponReceive;
use App\Services\CouponService;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class WindowCouponController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $this->userId();
        $storeId = $this->storeId();
        if (empty($this->user()->mobile)) {
            return $this->success(null);
        }
        $isolate = $this->isolate();
        $uniacid = $this->uniacid();
        $model = WindowCouponCoupon::where("uniacid", $this->uniacid())
            ->whereDoesntHave('receives', function ($q) use ($userId) {
                return $q->where('userId', $userId);
            })
            ->when("pos", function ($q) use ($request, $storeId, $uniacid, $isolate) {
                $q->where('pos', $request->pos);
                if ($request->pos != 1) {
                    $q->where(function ($q) use ($storeId, $uniacid, $isolate) {
                        return $q->whereHas('stores', function ($q) use ($storeId, $uniacid) {
                            return $q->where(function ($q) use ($storeId, $uniacid) {
                                return $q->where('storeId', $storeId)->where('type', 2);
                            })->orWhere(function ($q) use ($storeId, $uniacid) {
                                return $q->where('storeId', '!=', $storeId)->where('type', 3);
                            });
                        })->when($isolate == 0, function ($q) use ($uniacid) {
                            return $q->orWhere(function ($q) use ($uniacid) {
                                return $q->where('storeType', 1)->where('uniacid', $uniacid)->where('storeId', 0);
                            });
                        });
                    });
                } else {
                    $q->where('storeId', $this->isolateStore());
                }
                return $q;
            })
            ->where("startTime", '<=', date("Y-m-d H:i:s", time()))
            ->where("endTime", '>=', date("Y-m-d H:i:s", time()))
            ->first();
        if ($model) {
            $model->setAppends([
                'couponList', 'stateFormat'
            ]);
        }
        if ($model->receiveType == 1) {
            CouponService::issue($model->couponGive, $this->userId(), 2);
            CouponReceive::create([
                'uniacid' => $this->uniacid(),
                'windowCouponId' => $model->id,
                'userId' => $this->userId(),
                'balance' => $model->balance ?: 0.00,
                'integral' => $model->integral ?: 0,
                'coupon' => $model->couponGive ?: '',
                'data' => $model,
            ]);
        }
        return $this->success($model);
    }

    /**
     * 领券
     */
    public function store(Request $request)
    {
        $userId = $this->userId();
        $model = WindowCouponCoupon::where("uniacid", $this->uniacid())
            ->whereDoesntHave('receives', function ($q) use ($userId) {
                return $q->where('userId', $userId);
            })
            ->where('id', $request->id)
            ->where('uniacid', $this->uniacid())
            ->where('receiveType', 2)
            ->where("startTime", '<=', date("Y-m-d H:i:s", time()))
            ->where("endTime", '>=', date("Y-m-d H:i:s", time()))
            ->first();
        if (empty($model)) {
            return $this->failed('活动不存在或已结束');
        }
        CouponService::issue($model->couponGive, $this->userId(), 2);
        CouponReceive::create([
            'uniacid' => $this->uniacid(),
            'windowCouponId' => $model->id,
            'userId' => $this->userId(),
            'balance' => $model->balance ?: 0.00,
            'integral' => $model->integral ?: 0,
            'coupon' => $model->couponGive ?: '',
            'data' => $model,
        ]);
        return $this->success(null, '领取成功');
    }
}
