<?php

namespace App\Services;
use App\Models\Coupon\MemberCoupon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\CouponPack\Order;
use App\Models\Order\OrderIndex;
class CouponPackOderService
{
    /**
     * 退款
     */
    public  static function refund($orderId)
    {
        DB::beginTransaction();
        try {
            $orderIndex = OrderIndex::whereHas('couponPack', function ($q) use ($orderId) {
                return $q->whereIn('state', [3, 4, 5, 6, 7])->where('id', $orderId);
            })->paid()->first();

            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            $orderIndex->couponPack->state=8;
            $orderIndex->couponPack->save();
            $res = PayService::refund($orderIndex->couponPack, $orderIndex->uniacid, $orderIndex->payTempId);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage() . $e->getFile() . $e->getLine());
            throw new BadRequestException($e->getMessage());
        }
    }
}
