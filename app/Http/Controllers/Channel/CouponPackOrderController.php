<?php

namespace App\Http\Controllers\Channel;

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
use App\Services\CouponPackOderService;
use App\Services\MenuService;
use App\Services\OrderService;
use App\Services\UserService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Order\OrderIndex;
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
        $isolate =  $this->isolate();
        $list = Order::with(['user','activity'])->where("uniacid", $this->uniacid())
            ->where('state', 6)
            ->when($isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 1)->where('storeId', $storeId);
                });
            })
            ->when(!$isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 0)->when($storeId, function ($q) use ($storeId) {
                        return $q->where('storeId', $storeId);
                    });
                });
            })
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId();
        $model = Order::where("uniacid", $this->uniacid())
            ->where('orderSn', $id)
            ->first();
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        return $this->success($model);
    }

    /**
     * 退款
     */
    public function refund(Request $request, $id)
    {
        try {
            $orderIndex = OrderIndex::whereHas('couponPack', function ($q) use ($id) {
                return $q->whereIn('state', [2, 3, 4, 5, 6, 7])->where('id', $id);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            if (CouponPackOderService::refund($id)) {
                return $this->success([], '操作完成,已退款');
            }
            return $this->failed('退款失败');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
