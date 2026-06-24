<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Coupon\Activity;
use App\Models\Coupon\Coupon;
use App\Models\Coupon\Regift;
use App\Models\FullSub\FullSub;
use App\Models\FullSub\Goods;
use App\Models\FullSub\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class CouponActivityController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Activity::where("uniacid", $this->uniacid())
            ->where("startTime", '<=', date("Y-m-d H:i:s", time()))
            ->where("endTime", '>=', date("Y-m-d H:i:s", time()))
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }


    public function show(Request $request, $id)
    {
        try {
            $model = Activity::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->setAppends(['couponList']);
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function receive(Request $request, $id)
    {
        try {
            $lock_key = 'couponRegift:' . $id;
            $is_lock  = Cache::lock($lock_key)->get(); // 加锁
            if (!$is_lock) { // 获取锁权限
                // 防止死锁
                throw new BadRequestException('系统繁忙请稍后再试');
            }
        } catch (\Exception $e) {
        } finally {
            optional($lock_key)->forceRelease();
        }

        $model = Regift::where('uniacid', $this->uniacid())
            ->whereHas('memberCoupon')
            ->where('id', $id)
            ->where('state', 0)
            ->first();
        if (empty($model)) {
            throw new BadRequestException('优惠券不存在');
        }
        $model->state = 1;
        $model->receiveMemberId = $this->userId();
        $model->save();
        $model->memberCoupon->userId = $model->receiveMemberId;
        $model->memberCoupon->state = 1;
        $model->memberCoupon->channel = 11;
        $model->memberCoupon->save();
        return $this->success('领取成功');
    }
}
