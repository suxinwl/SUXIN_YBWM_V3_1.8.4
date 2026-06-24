<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Coupon\Coupon;
use App\Models\Coupon\MemberCoupon;
use App\Models\FullSub\FullSub;
use App\Models\FullSub\Goods;
use App\Models\FullSub\Store;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class CouponReceiveController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $list = MemberCoupon::with(['coupon', 'admin', 'member' => function ($q) {
            return $q->select(['id', 'nickname', 'mobile']);
        }])->where('uniacid', $this->uniacid())
            ->when($this->isolate(), function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->when($request->type, function ($q) use ($request) {
                return $q->whereHas("coupon", function ($q) use ($request) {
                    return $q->where('type', $request->type);
                });
            })
            ->when($request->verification, function ($q) use ($request, $storeId) {
                return $q->where('adminId', ">", 0)
                    ->where('storeId', $storeId);
            })
            ->when($request->state, function ($q) use ($request) {
                return $q->where('state', $request->state);
            })
            ->when($request->userId, function ($q) use ($request) {
                return $q->where('userId', $request->userId);
            })
            ->when($request->couponName, function ($q) use ($request) {
                return $q->whereHas("coupon", function ($q) use ($request) {
                    return $q->where('name', "like", "%{$request->name}%");
                });
            })
            ->when($request->userName, function ($q) use ($request) {
                return $q->whereHas("member", function ($q) use ($request) {
                    return $q->where('nickname', "like", "%{$request->userName}%")->orWhere('mobile', "like", "%{$request->userName}%");
                });
            })
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->startTime);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->endTime);
            })
            ->orderBy('updated_at', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            MemberCoupon::where('uniacid', $this->uniacid())
                ->where('state', 1)
                ->whereIn('id', $idArray)->update(['state' => 3]);
            return $this->success([], '作废成功');
        } catch (\Exception $e) {
            return $this->failed('作废失败');
        }
    }

    public function verification(Request $request, $id)
    {
        $coupon = MemberCoupon::with(['coupon' => function ($q) {
            return $q->select(['id', 'name', 'type', 'channel', 'state']);
        }])->where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->find($id);
        if ($coupon->coupon->storeType == 2 && !in_array($this->storeId(), $coupon->coupon->storeIds)) {
            return $this->failed('核销失败，该券不支持当前门店');
        }
        if ($coupon->coupon->storeType == 3 && in_array($this->storeId(), $coupon->coupon->storeIds)) {
            return $this->failed('核销失败，该券不支持当前门店');
        }
        if (empty($coupon)) {
            return $this->failed('核销失败，无效的券码');
        }
        if ($coupon->state == 0) {
            return $this->failed('优惠券已过期');
        }
        if ($coupon->state == 2) {
            return $this->failed('优惠券已使用');
        }
        $coupon->adminId = $this->userId();
        $coupon->storeId = $this->storeId();
        $coupon->state = 2;
        $coupon->save();
        return $this->success([], '核销成功');
    }
}
