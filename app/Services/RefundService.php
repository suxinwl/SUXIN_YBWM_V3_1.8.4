<?php

namespace App\Services;

use App\Models\Order\OrderIndex;
use App\Models\RefundOrder;
use App\Traits\ResourceTrait;
use DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class RefundService
{
    public static function userApply($orderSn)
    {
        $orderIndex = OrderIndex::where('orderSn', $orderSn)->where('state', 2)->first();
        if (empty($orderIndex)) {
            throw new BadRequestException("数据不存在");
        }
        RefundOrder::create([
            'takeOutNo' => $orderIndex->orderSn,
            'state' => 0,
            'source' => $orderIndex->score,
            'storId' => $orderIndex->storeId,
            'userId' => $orderIndex->userId,
            'money' => $orderIndex->subOrder->money,
            'uniacid' => $orderIndex->uniacid,
            'why' => $orderIndex->subOrder->refundCause,
            'notes' => $orderIndex->subOrder->notes,
        ]);
        return true;
    }

    public static function StoreApply($orderSn, $source, $adminId)
    {
        try {
            $orderIndex = OrderIndex::where('orderSn', $orderSn)->where('state', 2)->first();
            if (empty($orderIndex)) {
                throw new BadRequestException("数据不存在");
            }
            RefundOrder::create([
                'takeOutNo' => $orderIndex->orderSn,
                'state' => $source,
                'source' => 11,
                'storId' => $orderIndex->storeId,
                'userId' => $orderIndex->userId,
                'money' => $orderIndex->subOrder->money,
                'uniacid' => $orderIndex->uniacid,
                'why' => $orderIndex->subOrder->refundCause,
                'notes' => $orderIndex->subOrder->notes,
            ]);
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
        }
        return true;
    }

    public static function refund($orderSn, $refundMoney = 0, $adminId = 0, $notes = '')
    {
        $orderIndex = OrderIndex::where('orderSn', $orderSn)->where('state', 2)->first();
        $order = [
            'takeOutNo' => $orderIndex->orderSn,
            'openid' => $orderIndex->payer,
            'transaction_id' => $orderIndex->thirdNo,
            'total_amount' => $orderIndex->order->money,
            'refund_amount' => $refundMoney,
        ];
        if (in_array($orderIndex->order->state, [1, 2, 3, 4, 5, 6])) {
            if (empty($log)) {
                $orderIndex->order->state = 7;
                $orderIndex->order->setLog('门店发起退款，原因:' . $notes);
            }
            if ($orderIndex->payType > 0 && $orderIndex->payChannel == 1) {
                if (!StoreAccountService::refundDirectly($orderIndex->storeId, $refundMoney, $orderIndex->userId, $orderIndex->takeOutNo)) {
                    DB::rollBack();
                    throw  new BadRequestException('申请退款失败');
                }
            }
        } else {
            if ($orderIndex->payType > 0 && $orderIndex->payChannel == 1) {
                if (!StoreAccountService::refund($orderIndex->storeId, $refundMoney, $adminId, $orderIndex->takeOutNo)) {
                    DB::rollBack();
                    throw  new BadRequestException('申请退款失败');
                }
            }
        }
        $refundOrder = RefundOrder::where('takeOutNo', $orderIndex->orderSn)->first();
        if (empty($refundOrder)) {
            $res = PayService::refund($order, $orderIndex->uniacid, $orderIndex->payTempId);
        } else {
            $res = true;
        }
        return true;
    }
}
