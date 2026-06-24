<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Coupon\Coupon;
use App\Models\Coupon\MemberCoupon;
use App\Models\Coupon\Regift;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\UserAccount;
use App\Services\CouponService;
use App\Services\MenuService;
use App\Services\UserService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CouponRegiftController extends ApiController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Regift::where("uniacid", $this->uniacid())
            ->where('userId', $this->userId())
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == "giveing") {
                    return $q->where('state', 0);
                }
                if ($request->state == "receive") {
                    return $q->where('state', 1);
                }
                return $q->where('state', 1);
            })
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $model = Regift::where('uniacid', $this->uniacid())
            ->whereHas('memberCoupon')
            ->whereHas('coupon')
            ->where('memberCouponId', $id)
            ->orderBy('id','desc')
            ->first();
        if (empty($model)) {
            throw new BadRequestException('优惠券不存在');
        }
        return $this->success($model);
    }

    public function store(Request $request)
    {
        $id = $request->id;
        $coupon = MemberCoupon::where('uniacid', $this->uniacid())
            ->where('id', $id)
            ->first();
        if (empty($coupon)) {
            throw new BadRequestException('优惠券不存在');
        }
        $model = Regift::create([
            'uniacid' => $coupon->uniacid,
            'couponId' => $coupon->couponId,
            'memberCouponId' => $coupon->id,
            'state' => 0,
            'expiredTime' => date("Y-m-d H:i:s", time() + 3600 * 24),
            'userId' => $this->userId()
        ]);
        return $this->success($model->id);
    }

    public function destroy(Request $request, $id)
    {
        $coupon = Regift::where('uniacid', $this->uniacid())
            ->where('userId', $this->userId())
            ->where('id', $id)
            ->first();
        if (empty($coupon)) {
            throw new BadRequestException('优惠券不存在');
        }
        $coupon->state = 2;
        $coupon->save();
        return $this->success('操作成功');
    }

    public function receive(Request $request, $id)
    {
        try {
            $lock_key = 'couponRegift:' . $id;
            $is_lock  = Redis::setnx($lock_key, 1); // 加锁
            if (!$is_lock) { // 获取锁权限
                // 防止死锁
                Redis::del($lock_key);
                throw new BadRequestException('系统繁忙请稍后再试');
            } else {
                if (Redis::ttl($lock_key) == -1) {
                    Redis::expire($lock_key, 1);
                }
            }
        } catch (\Exception $e) {
            Redis::del($lock_key);
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
