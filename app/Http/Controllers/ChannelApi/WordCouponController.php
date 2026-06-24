<?php

namespace App\Http\Controllers\ChannelApi;

use App\Events\WordCouponEvent;
use App\Models\WindowCoupon\Coupon as WindowCouponCoupon;
use App\Models\WindowCoupon\CouponReceive;
use App\Models\WordCoupon\Coupon;
use App\Models\WordCoupon\Receive;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class WordCouponController extends ApiController
{

    /**
     * 领券
     */
    public function store(Request $request)
    {
        $userId = $this->userId();
        $coupon = Coupon::where("uniacid", $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->where('id', $request->id)
            ->where('uniacid', $this->uniacid())
            ->where("startTime", '<=', date("Y-m-d H:i:s", time()))
            ->where("endTime", '>=', date("Y-m-d H:i:s", time()))
            ->first();
        if (empty($coupon)) {
            return $this->failed('活动不存在或已结束');
        }
        if ($coupon['word'] !== $request->word) {
            return $this->failed('口令不正确');
        }
        if ($coupon->inventoryLimit['userLimitSwitch'] == 1 && $coupon->inventory <= 0) {
            throw new BadRequestException('活动库存不足');
        }
        $couponId = $request->id;
        $limitKey = "wordCoupon:userlimit:{$couponId}{$userId}";
        $userlimit = Cache::get($limitKey, 0);
        if ($coupon->inventoryLimit['userLimitSwitch'] == 1 && $userlimit >= $coupon->inventoryLimit['userLimit']) {
            throw new BadRequestException('领取已达上限');
        }
        $dayLimitKey = "wordCoupon:userDaylimit:{$couponId}" . date("Ymd") . ":{$userId}";
        $dayLimit = Cache::get($dayLimitKey, 0);
        if ($coupon->inventoryLimit['userDaySwitch'] == 1 && $dayLimit >= $coupon->inventoryLimit['userDayLimit']) {
            throw new BadRequestException('今日领取已达上限');
        }
        $receive = Receive::create([
            'uniacid' => $this->uniacid(),
            'wordCouponId' => $coupon->id,
            'userId' => $this->userId(),
            'storeId' => $this->isolateStore(),
            'balance' => $coupon->balanceSwitch ? $coupon->balance : 0,
            'integral' => $coupon->integralSwitch ? $coupon->integral : 0,
            'coupon' => $coupon->couponSwitch ? $coupon->couponGive : [],
        ]);
        $coupon->subInventory(1);
        event(new WordCouponEvent($receive));
        Cache::increment($limitKey);
        Cache::increment($dayLimitKey);
        return $this->success($receive, '领取成功');
    }

    public function show(Request $request, $id)
    {
        $model = Coupon::where("uniacid", $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->where('id', $id)
            ->where('uniacid', $this->uniacid())
            ->where("startTime", '<=', date("Y-m-d H:i:s", time()))
            ->where("endTime", '>=', date("Y-m-d H:i:s", time()))
            ->first();
        if (!$model) {
            return $this->failed('活动已结束');
        }
        $model->setAppends(['couponGive']);

        return $this->success($model);
    }
    public function index(Request $request)
    {
        $model = Coupon::where("uniacid", $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('uniacid', $this->uniacid())
            ->where("startTime", '<=', date("Y-m-d H:i:s", time()))
            ->where("endTime", '>=', date("Y-m-d H:i:s", time()))
            ->first();
        if (!$model) {
            return $this->failed('活动不存在或已结束');
        }
        $model->setAppends(['couponGive']);
        return $this->success($model);
    }
}
