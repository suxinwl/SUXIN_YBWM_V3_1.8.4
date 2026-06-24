<?php

namespace App\Http\Controllers\Channel;

use App\Exports\PersonpayOrderDataExport;
use App\Events\OrderCollectEvent;
use App\Events\OrderMessageEvent;
use App\Events\PayGiftEvent;
use App\Http\Controllers\Controller;
use App\Jobs\OrderStatisticsJob;
use App\Models\CostomPay;
use App\Models\Coupon\MemberCoupon;
use Illuminate\Http\Request;
use App\Models\HandleLog;
use App\Models\MemberAccount;
use App\Models\MemberAccountLog;
use App\Models\Order\Discount;
use App\Models\Order\OrderIndex;
use App\Models\PayConfig;
use App\Models\PersionPay\Checkout;
use App\Models\PersionPayOrder;
use App\Models\RefundOrder;
use App\Models\StatisticsDay;
use App\Models\StoredValue;
use App\Models\StoredValueOrder;
use App\Services\MemberAccountService;
use App\Services\OrderNotifyService;
use App\Services\PayService;
use App\Services\StaticService;
use App\Services\StoreAccountService;
use App\Traits\StatisticsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PersonPayOrderController extends ApiController
{
    use StatisticsTrait;
    // GET 索引/列表
    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $user = $this->user();
        $isolate = $this->isolate();
        $timeArr = $this->timeArr(true);
        $list = PersionPayOrder::with(['orderIndex', 'user' => function ($q) {
            return $q->select(['id', 'nickname', 'mobile', 'avatar']);
        }, 'store' => function ($q) {
            return $q->select(['id', 'name']);
        }, 'orderIndex'])
            ->where('uniacid', $this->uniacid())
            ->whereIn('state', [6, 8])
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where(
                    function ($q) use ($request) {
                        return $q->where('orderSn', "like", "%$request->keyword%");
                    }
                );
            })
            ->when($request->payType, function ($q) use ($request) {
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
            })
            ->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where('created_at', '>=', $timeArr['startTime'])
                    ->where('created_at', '<=', $timeArr['endTime']);
            })
            ->when($request->source, function ($q) use ($request) {
                return $q->where('score', appType($request->source));
            })
            ->when($request->userKeyword, function ($q) use ($request) {
                return $q->whereHas('user', function ($q) use ($request) {
                    return $q->where('mobile', "like", "%$request->userKeyword%")
                        ->orWhere('nickname', "like", "%$request->userKeyword%");
                });
            })
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == 'pay') {
                    return $q->where('state', 6);
                }
                if ($request->state == 'close') {
                    return $q->where('state', 0);
                }
                if ($request->state == 'refund') {
                    return $q->where('state', 8);
                }
            })->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })->when($isolate, function ($q) use ($storeId, $isolate) {
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
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function show(Request $request, $id)
    {
        try {
            $model = PersionPayOrder::with(['user' => function ($q) {
                return $q->select(['id', 'nickname', 'mobile', 'avatar']);
            }, 'store' => function ($q) {
                return $q->select(['id', 'name']);
            }, 'orderIndex'])->where('uniacid', $this->uniacid())->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    /**
     * 退款
     */
    public  function refund(Request $request, $orderId)
    {
        DB::beginTransaction();
        try {
            $orderIndex = OrderIndex::whereHas('personPayOrder', function ($q) use ($orderId) {
                return $q->where('state', 6)->where('orderSn', $orderId);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            $refundMoney = $orderIndex->personPayOrder->money;
            $adminId = $this->userId();
            $reason = $request->reason;
            $log = null;
            $order = [
                'takeOutNo' => $orderIndex->orderSn,
                'openid' => $orderIndex->payer,
                'transaction_id' => $orderIndex->thirdNo,
                'total_amount' => $orderIndex->personPayOrder->money,
                'refund_amount' => $refundMoney,
            ];
            // $orderIndex->personPayOrder->setLog('门店发起退款，原因:' . $notes);
            if ($orderIndex->payChannel == 1) {
                if (!StoreAccountService::refundDirectly($orderIndex->storeId, $refundMoney, $adminId, $orderIndex->takeOutNo)) {
                    DB::rollBack();
                    throw  new BadRequestException('申请退款失败');
                }
            }
            if ($orderIndex->payType != 6 && $orderIndex->payType < 100) {
                $refundOrder = RefundOrder::where('takeOutNo', $orderIndex->orderSn)->first();
                if (empty($refundOrder)) {
                    $res = PayService::refund($order, $orderIndex->uniacid, $orderIndex->payTempId);
                } else {
                    $res = true;
                }
            } else {
                $res = true;
            }

            if ($res) {
                $orderIndex->personPayOrder->refundMoney = $orderIndex->personPayOrder->refundMoney + $refundMoney;
                $orderIndex->personPayOrder->reason = $reason;
                $orderIndex->state =  8;
                // $orderIndex->order->setLog(!empty($log) ? $log : "退款金额:{$refundMoney}");
                if ($orderIndex->state == 8) {
                    if ($orderIndex->personPayOrder->couponId) {
                        MemberCoupon::where('orderSn', $orderIndex->personPayOrder->orderSn)->update([
                            'state' => 1,
                            'orderId' => 0,
                            'orderSn' => null,
                            'updated_at' => null
                        ]);
                    }
                    if ($orderIndex->personPayOrder->integral > 0) {
                        MemberAccountService::changeIntegral($orderIndex->userId, 2, $orderIndex->personPayOrder->integral, MemberAccountLog::INTEGRAL_ORDER_GIVE, 0, "订单{$orderIndex->orderSn}赠送退回", $orderIndex->orderSn);
                    }

                    if ($orderIndex->personPayOrder->exp > 0) {
                        MemberAccountService::changeExp($orderIndex->userId, 2, $orderIndex->personPayOrder->exp, MemberAccountLog::EXP_ORDER_GIVE, 0, "订单{$orderIndex->orderSn}赠送退回", $orderIndex->orderSn);
                    }
                    if ($orderIndex->userID > 0) {
                        $orderIndex->order->refundUserPayStore();
                        $orderIndex->user->isPay--;
                        $orderIndex->user->save();
                    }
                }
                $orderIndex->personPayOrder->state = 8;
                $orderIndex->personPayOrder->save();
                $orderIndex->save();
                // if ($orderIndex->personPayOrder->statisticsData) {
                //     StatisticsDay::where(function ($q) use ($orderIndex) {
                //         return $q->where(function ($q) use ($orderIndex) {
                //             return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", 0);
                //         })->orWhere(function ($q) use ($orderIndex) {
                //             return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", $orderIndex->storeId);
                //         });
                //     })->where("day", date("Y-m-d", strtotime($orderIndex->personPayOrder->created_at)))
                //         ->where("h", date("H", strtotime($orderIndex->personPayOrder->created_at)))
                //         ->update($orderIndex->personPayOrder->statisticsData);
                // }
                if ($orderIndex->personPayOrder->changeBeforState == 6) {
                    /**
                     * 支付有利撤回
                     */
                    if ($orderIndex->personPayOrder->payGiftId) {
                        Event(new PayGiftEvent($orderIndex->personPayOrder, 'refund'));
                    }
                }
                DB::commit();
                Event(new OrderMessageEvent($orderIndex->personPayOrder, 'refund'));
                StaticService::tongji($orderIndex->orderSn);
                //dispatch(new OrderStatisticsJob($orderIndex->orderSn));
                return $this->success(null, '退款成功');
            }
            DB::rollBack();
            return $this->failed('退款失败');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }


    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            if ($request->money <= 0) {
                return $this->failed('支付金额不正确');
            }
            $model =   new Checkout([
                'uniacid' => $this->uniacid(),
                'storeId' => $this->storeId(),
                'userId' => $request->userId ?? 0,
                'couponId' => $request->couponId ?? 0,
                'sellMoney' => $request->money,
                'score' => $this->appType()
            ]);
            $checkout = $model->toArray();
            $payOrder = new PersionPayOrder($checkout);
            $payOrder->orderSn = getTakeOutNo();
            $payOrder->remarks = $request->remarks ?? '';
            $payOrder->score = $this->appType();
            $payOrder->state = 1;
            $payOrder->save();
            // foreach ($checkout['discounts'] as $key => $discount) {
            //     $discount['uniacid'] = $checkout['uniacid'];
            //     $discount['orderId'] = 0;
            //     $discount['orderSn'] = $payOrder->orderSn;
            //     $discount['storeId'] = $checkout['storeId'];
            //     $discount['userId'] = $checkout['userId'];
            //     Discount::create($discount);
            // }
            // if ($payOrder->couponId) {
            //     MemberCoupon::where('id', $payOrder->couponId)->update([
            //         'state' => 2,
            //         'updated_at' => date("Y-m-d H:i:s", time()),
            //         'orderId' => 0,
            //         'orderSn' => $payOrder->orderSn
            //     ]);
            // }
            // $payOrder = PersionPayOrder::create([
            //     'uniacid' => $this->uniacid(),
            //     'userId' => $request->userId ?? 0,
            //     'storeId' => $this->storeId(),
            //     'money' => $request->money,
            //     'sellMoney' => $request->money,
            //     'orderSn' => getTakeOutNo(),
            //     'score' => $this->appType(),
            //     'state' => 1,
            //     'adminId' => $this->userId(),
            //     'remarks' => $request->remarks ?? ''
            // ]);
            // $payOrder->refresh();

            if ($request->payType == 'cash') {
                $order = [
                    'orderSn' => $payOrder->orderSn,
                    'takeOutNo' => $payOrder->orderSn,
                    'amount' => $payOrder->money,
                    'desc' => "收款",
                    'payTempId' => 0,
                    'trade_type' => 6,
                    'payChannel' => 2,
                    'payer' => ['openid' => null]
                ];
                if (!OrderNotifyService::personPay($order, $order['takeOutNo'], $order['payTempId'])) {
                    DB::rollBack();
                    return $this->failed('支付失败');
                }
            } elseif ($request->payType == "authCode") {
                $orderIndex = OrderIndex::where('orderSn', $payOrder->orderSn)->first();
                $order = [
                    'orderSn' => $orderIndex->orderSn,
                    'takeOutNo' => $orderIndex->orderSn,
                    'amount' => $payOrder->money,
                    'desc' => "商家收款",
                    'balance' => $orderIndex->balance,
                    'auth_code' => $request->authCode,
                    'uniacid' => $orderIndex->uniacid,
                    'storeId' => $orderIndex->storeId,
                    'orderType' => $orderIndex->type,
                    'userId' => $payOrder->userId ?? 0,
                    'storePay' => $orderIndex->store->payChange,
                    'isolate' => $orderIndex->store->isolate
                ];
                $res = PayService::micropay($order);
                if ($payOrder->userId == 0) {
                    $payOrder->userId = $res['userId'];
                }
                $orderIndex->save();
                $payOrder->save();
                if (!OrderNotifyService::personPay($res, $order['takeOutNo'], $res['payTempId'])) {
                    DB::rollBack();
                    return $this->failed('支付失败');
                }
            } elseif ($request->payType == "balance") {
                if (empty($request->userId)) {
                    return $this->failed("请核对会员账号");
                }
                $order = [
                    'orderSn' => $payOrder->orderSn,
                    'takeOutNo' => $payOrder->orderSn,
                    'amount' => $payOrder->money,
                    'desc' => "代客下单",
                    'auth_code' => $request->authCode,
                    'uniacid' => $payOrder->uniacid,
                    'storeId' => $payOrder->storeId,
                    'orderType' => $payOrder->type,
                    'userId' => $request->payUserId,
                    'storePay' => $payOrder->store->payChange,
                    'balance' => $payOrder->orderIndex->balance
                ];
                $payConfig = PayConfig::where('uniacid', $order['uniacid'])
                    ->where('payType', 'balance')
                    ->first();
                if (empty($payConfig)) {
                    return $this->failed("暂不支持该支付方式");
                }
                $res = PayService::pay($order, $order['uniacid'], $payConfig->id, $this->appType());
                if (!$res) {
                    DB::rollBack();
                    return $this->failed('支付失败');
                }
            } elseif ($request->payType == 'costomPay') {
                $costomPay = CostomPay::find($request->costomPayId);
                if (!$costomPay) {
                    return $this->failed('无效的支付渠道');
                }
                $order = [
                    'takeOutNo' => $payOrder->orderSn,
                    'orderSn' => $payOrder->orderSn,
                    'amount' => $request->money ?? $payOrder->money,
                    'desc' => "代客下单",
                    'payTempId' => 0,
                    'trade_type' => $costomPay->payId,
                    'payChannel' => 2,
                    'payer' => ['openid' => null]
                ];
                if (!OrderNotifyService::personPay($order, $order['takeOutNo'], $order['payTempId'])) {
                    DB::rollBack();
                    return $this->failed("支付失败");
                };
            } else {
                return $this->failed('不支持该支付渠道');
            }
            DB::commit();
            return $this->success([], '成功收款' . $payOrder->money . '元');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    public function orderDataExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: 2;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new PersonpayOrderDataExport($params), 'personPayOrderData.xlsx');
    }
}
