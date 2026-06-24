<?php

namespace App\Http\Controllers\ChannelApi;

use App\Enums\PayEnum;
use App\Models\MemberAccountLog;
use App\Models\Order\OrderIndex;
use Illuminate\Http\Request;
use App\Models\PointsMall;
use App\Models\PointsMall\Checkout;
use App\Models\PointsMall\Order;
use App\Models\RefundOrder;
use App\Services\MemberAccountService;
use App\Services\OrderNotifyService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PointsMallOrderController extends ApiController
{
    public function Index(Request $request)
    {
        $list = Order::where('uniacid', $this->uniacid())
            ->where('userId', $this->userId())
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == 'close') {
                    return $q->close();
                }
                if ($request->state == 'unpaid') {
                    return $q->unpaid();
                }
                if ($request->state == 'unDelivery') {
                    return $q->unDelivery();
                }
                if ($request->state == 'delivery') {
                    return $q->delivery();
                }
                if ($request->state == 'complete') {
                    return $q->complete();
                }
                if ($request->state == 'refund') {
                    return $q->refund();
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }
    public function show(Request $request, $orderSn)
    {
        $model = Order::where("uniacid", $this->uniacid())
            ->where('userId', $this->userId())
            ->where("orderSn", $orderSn)
            ->first();
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->setAppends(['verificationCode', 'stateForamt', 'goodsCategory', 'diningTypeFormat']);
        return $this->success($model);
    }

    public function refundApply(Request $request, $orderSn)
    {
        $model = Order::where("uniacid", $this->uniacid())
            ->where('userId', $this->userId())
            ->where("orderSn", $orderSn)
            ->first();
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->afterSaleTime = Carbon::now()->toDateTimeString();
        $model->state = 7;
        $model->save();
        return $this->success([], '申请成功');
    }

    public function close(Request $request, $orderSn)
    {

        try {
            $orderIndex = OrderIndex::where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }

            foreach ($orderIndex->orderPay as $key => $orderPay) {
                if ($orderPay->payType == PayEnum::POINTS) {
                    $res = MemberAccountService::changeIntegral($orderIndex->userId, 1, intval($orderPay->money), MemberAccountLog::INTEGRAL_ORDER_REFUND, 0, "订单退款" . intval($orderPay->money) . "积分");
                }
            }

            $orderIndex->subOrder->state = 0;
            $orderIndex->subOrder->save();
            $orderIndex->state = 0;
            $orderIndex->save();
            return $this->success([], '取消成功');
        } catch (\Exception $e) {


            throw new BadRequestException($e->getMessage());
        }
    }


    /**
     * 完成订单
     */
    public function complete(Request $request, $orderSn)
    {
        $model = Order::where("uniacid", $this->uniacid())
            ->where('userId', $this->userId())
            ->where("orderSn", $orderSn)
            ->first();
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->state = 6;
        $model->completionTime = Carbon::now()->toDateTimeString();
        $model->save();
        $model->state = 6;
        $model->orderIndex->save();
        return $this->success();
    }
}
