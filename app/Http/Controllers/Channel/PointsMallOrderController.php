<?php

namespace App\Http\Controllers\Channel;

use App\Enums\PayEnum;
use App\Models\MemberAccountLog;
use App\Models\Order\OrderIndex;
use Illuminate\Http\Request;
use App\Models\PointsMall;
use App\Models\PointsMall\Order;
use App\Models\RefundOrder;
use App\Services\MemberAccountService;
use App\Services\PayService;
use App\Traits\StatisticsTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PointsMallOrderController extends ApiController
{
    use StatisticsTrait;
    public function Index(Request $request)
    {
        $storeId = $this->storeId();
        $isolate =  $this->isolate();
        $user = $this->user();
        $timeArr = $this->timeArr(true);
        $list = Order::where('uniacid', $this->uniacid())
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
                if ($request->state == 'refundApply') {
                    return $q->refundApply();
                }
                if ($request->state == 'refund') {
                    return $q->refund();
                }
            })->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where($request->timeChannel ?? 'created_at', '>=', $timeArr['startTime'])
                    ->where($request->timeChannel ?? 'created_at', '<=', $timeArr['endTime']);
            })->when($request->keyword, function ($q) use ($request) {
                return $q->where(
                    function ($q) use ($request) {
                        return $q->where('orderSn', "like", "%$request->keyword%")->orWhere(function ($q) use ($request) {
                            return $q->whereHas('user', function ($q) use ($request) {
                                return $q->where('mobile', "like", "%$request->keyword%")
                                    ->orWhere('nickname', "li1ke", "%$request->keyword%");
                            });
                        });
                    }
                );
            })->when($request->userKeyword, function ($q) use ($request) {
                return $q->whereHas('user', function ($q) use ($request) {
                    return $q->where('mobile', "like", "%$request->userKeyword%")
                        ->orWhere('nickname', "li1ke", "%$request->userKeyword%");
                });
            })->when($request->payType, function ($q) use ($request) {
                return $q->whereHas('orderIndex', function ($q) use ($request) {
                    if ($request->payType == 'wexin') {
                        return $q->weixin();
                    }
                    if ($request->payType == 'ali') {
                        return $q->ali();
                    }
                    if ($request->payType == 'balance') {
                        return $q->balance();
                    }
                    return $q;
                });
            })->when($request->source, function ($q) use ($request) {
                return $q->where('score', appType($request->source));
            })->when($isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 1)->where('storeId', $storeId);
                });
            })
            ->when(!$isolate, function ($q) use ($storeId) {
                return $q->where(function ($q) use ($storeId) {
                    return $q->whereHas('store', function ($q) use ($storeId) {
                        return $q->where('isolate', 0)->when($storeId, function ($q) use ($storeId) {
                            return $q->where('storeId', $storeId);
                        });
                    })->orWhere('storeId', 0);
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function show(Request $request, $orderSn)
    {
        $storeId = $this->storeId();
        $order =  Order::where('orderSn', $orderSn)
            ->where("uniacid", $this->uniacid())
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->first();
        if (empty($order)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($order);
    }

    public function delivery(Request $request, $orderSn)
    {
        $order = Order::where("uniacid", $this->uniacid())
            ->whereIn('state', [2, 3])
            ->where('orderSn', $orderSn)->first();
        if (empty($order)) {
            return $this->failed('订单不存在');
        }
        $order->deliveryName = $request->deliveryName;
        $order->deliverySn = $request->deliverySn;
        $order->deliveryTime = Carbon::now()->toDateTimeString();
        $order->state = 3;
        $order->save();
        return $this->success('发货成功');
    }


    /**
     * 退款
     */
    public   function refund(Request $request, $orderSn)
    {
        DB::beginTransaction();
        try {
            $orderIndex = OrderIndex::where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }

            foreach ($orderIndex->orderPay as $key => $orderPay) {
                if ($orderPay->payType == PayEnum::POINTS) {
                    $res = MemberAccountService::changeIntegral($orderIndex->userId, 1, intval($orderPay->money), MemberAccountLog::INTEGRAL_ORDER_REFUND, 0, "订单退款" . intval($orderPay->money) . "积分");
                } else {
                    $order = [
                        'takeOutNo' => $orderIndex->orderSn,
                        'openid' => $orderIndex->payer,
                        'transaction_id' => $orderIndex->thirdNo,
                        'total_amount' => $orderIndex->subOrder->money,
                        'refund_amount' => $orderIndex->subOrder->money,
                    ];
                    $refundOrder = RefundOrder::where('takeOutNo', $orderIndex->orderSn)->first();
                    if (empty($refundOrder)) {
                        $res = PayService::refund($order, $orderIndex->uniacid, $orderIndex->payTempId);
                    } else {
                        $res = true;
                    }
                }
            }
            if ($res) {
                $orderIndex->subOrder->afterSaleCompletion =   date("y-m-d H:i:s", time());
                $orderIndex->subOrder->refundMoney = $orderIndex->subOrder->money;
                $orderIndex->subOrder->state = 8;
                $orderIndex->subOrder->save();
                $orderIndex->state = 8;
                $orderIndex->save();
                PointsMall::where('id', $orderIndex->subOrder->goods['id'])->decrement('sales', 1);
                PointsMall::where('id', $orderIndex->subOrder->goods['id'])->increment('stock', 1);
                DB::commit();
                return $this->success([], '退款成功');
            }
            DB::rollBack();
            return $this->failed([], '退款失败');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }


    /**
     * 拒绝退款
     */
    public  function rejectRefund(Request $request, $orderSn)
    {
        DB::beginTransaction();
        try {
            $orderIndex = OrderIndex::where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            $orderIndex->subOrder->state = $orderIndex->subOrder->refundState;
            $orderIndex->subOrder->refundState = 0;
            $orderIndex->subOrder->save();
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }

    public function  verification(Request $request, $orderSn)
    {
        $model =  Order::where('orderSn', $orderSn)
            ->where("uniacid", $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('qrCode', $request->code)
            ->where('state', 2)
            ->first();
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $model->state = 6;
        $model->completionTime = Carbon::now()->toDateTimeString();
        $model->save();
        $model->state = 6;
        $model->orderIndex->save();
        DB::commit();
        return $this->success([], '核销成功');
    }

    public function notes(Request $request, $id)
    {
        $storeId = $this->storeId();
        $order =  Order::where('id', $id)->where("uniacid", $this->uniacid())->first();
        if (empty($order)) {
            throw new BadRequestException('数据不存在');
        }
        $order->storeNotes = $request->notes;
        $order->save();
        return $this->success([], '备注成功');
    }
}
