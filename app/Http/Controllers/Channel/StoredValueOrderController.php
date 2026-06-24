<?php

namespace App\Http\Controllers\Channel;

use App\Exports\StorevalueOrderDataExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HandleLog;
use App\Models\Order\OrderIndex;
use App\Models\PayConfig;
use App\Models\StoredValue;
use App\Models\StoredValueOrder;
use App\Services\OrderNotifyService;
use App\Services\PayService;
use App\Traits\StatisticsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class StoredValueOrderController extends ApiController
{
    use StatisticsTrait;
    // GET 索引/列表
    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $user = $this->user();
        $timeArr = $this->timeArr(true);
        $list = StoredValueOrder::with(['user' => function ($q) {
            return $q->select(['id', 'nickname', 'mobile', 'avatar']);
        }, 'store', 'orderIndex'])
            ->where('uniacid', $this->uniacid())
            ->where('state', 2)
            ->when($request->keyword, function ($q) use ($request) {
                return $q->whereHas('user', function ($q) use ($request) {
                    return $q->where('nickname', 'like', "%$request->keyword%")->orWhere('mobile', 'like', "%$request->keyword%");
                });
            })->when($request->score, function ($q) use ($request) {
                return $q->where('score', appType($request->score));
            })->when($request->startTime && $request->endTime, function ($q) use ($request, $timeArr) {
                return $q->where('created_at', '>=', $request->startTime)
                    ->where('created_at', '<=', $request->endTime);
            })
            ->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where('created_at', '>=', $timeArr['startTime'])
                    ->where('created_at', '<=', $timeArr['endTime']);
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->when($request->storeId, function ($q) use ($storeId) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('storeId', $storeId);
                });
            })

            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        try {
            $model = StoredValueOrder::with(['user' => function ($q) {
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

    public function List(Request $request)
    {
        try {
            $list = StoredValue::where("uniacid", $this->uniacid())
                ->get();
            return $this->success($list);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->failed($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            OrderIndex::where('type', 2)->where('uniacid', $this->uniacid())
                ->where('userId', $request->userId)->where('state', 1)->delete();
            StoredValueOrder::where('uniacid', $this->uniacid())
                ->where('userId', $request->userId)->where('state', 1)->delete();
            if ($request->id == 0) {
                if ($request->money <= 0) {
                    return $this->failed('最低充值金额不能为0');
                }
                $model = new StoredValue([
                    'amount' => $request->money,
                    "uniacid" => $this->uniacid(),
                    'userId' => $request->userId,
                    'storeId' => $this->storeId(),
                    'storeValueId' => 0,
                    'score' => $this->appType()
                ]);
            } else {
                $model = StoredValue::where('uniacid', $this->uniacid())->find($request->id);
                if (empty($model)) {
                    return $this->failed('数据不存在');
                }
            }
            try{
                $orderSn=getTakeOutNo();
                $payOrder = StoredValueOrder::create([
                    "uniacid" => $this->uniacid(),
                    'userId' => $request->userId,
                    'storeId' => $this->storeId(),
                    'orderSn' => $orderSn,
                    'money' => $model->amount,
                    'data' => collect($model->rule)->toArray(),
                    'state' => 1,
                    'score' => $this->appType(),
                    'expiredTime' => date("Y-m-d H:i:s", time() + 60 * 15),
                    'storeValueId' => $request->id
                ]);
            } catch (\Exception $e) {
                var_dump($e->getMessage());
            }
            if ($request->payType == 'cash') {
                // return $this->failed('不支持该支付渠道');
                $orderIndex = OrderIndex::where('orderSn', $payOrder->orderSn)->first();
                $order = [
                    'orderSn'=>$payOrder->orderSn,
                    'takeOutNo' => $payOrder->orderSn,
                    'amount' => $payOrder->money,
                    'desc' => "充值",
                    'payTempId' => 0,
                    'trade_type' => 6,
                    'payChannel' => 2,
                    'payer' => ['openid' => null]
                ];
                if (!OrderNotifyService::storeValue($order, $order['takeOutNo'], $order['payTempId'])) {
                    DB::rollBack();
                    return $this->failed('支付失败');
                }
            } elseif ($request->payType == "authCode") {
                $orderIndex = OrderIndex::where('orderSn', $payOrder->orderSn)->first();
                $order = [
                    'orderSn' => $orderIndex->orderSn,
                    'takeOutNo' => $orderIndex->orderSn,
                    'amount' => $payOrder->money,
                    'desc' => "充值",
                    'balance' => $orderIndex->balance,
                    'auth_code' => $request->authCode,
                    'uniacid' => $orderIndex->uniacid,
                    'storeId' => $orderIndex->storeId,
                    'orderType' => $orderIndex->type,
                    'userId' => $payOrder->userId ?? 0,
                    'storePay' => $orderIndex->subOrder->store->payChange,
                    'isolate'=> $orderIndex->subOrder->store->isolate
                ];
                $res = PayService::micropay($order);
                if ($payOrder->userId == 0) {
                    $payOrder->userId = $res['userId'];
                }
                $orderIndex->save();
                $payOrder->save();
                if (!OrderNotifyService::storeValue($res, $order['takeOutNo'], $res['payTempId'])) {
                    DB::rollBack();
                    return $this->failed('支付失败');
                }
            } elseif ($request->payType == "balance") {
                return $this->failed('不支持该支付渠道');
                // if (empty($request->userId)) {
                //     return $this->failed("请核对会员账号");
                // }
                // $order = [
                //     'orderSn'=>$payOrder->orderSn,
                //     'takeOutNo' => $payOrder->orderSn,
                //     'amount' => $payOrder->money,
                //     'desc' => "代客下单",
                //     'auth_code' => $request->authCode,
                //     'uniacid' => $payOrder->uniacid,
                //     'storeId' => $payOrder->storeId,
                //     'orderType' => $payOrder->type,
                //     'userId' => $request->payUserId,
                //     'storePay' => $payOrder->store->payChange,
                //     'balance' => $payOrder->orderIndex->balance
                // ];
                // $payConfig = PayConfig::where('uniacid', $order['uniacid'])
                //     ->where('payType', 'balance')
                //     ->first();
                // if (empty($payConfig)) {
                //     return $this->failed("暂不支持该支付方式");
                // }
                // $res = PayService::pay($order, $order['uniacid'], $payConfig->id, $this->appType());
                // if (!$res) {
                //     DB::rollBack();
                //     return $this->failed('支付失败');
                // }
            } else {
                return $this->failed('不支持该支付渠道');
            }
            DB::commit();
            return $this->success([], '充值成功');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->failed($e->getMessage());
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
        return Excel::download(new StorevalueOrderDataExport($params), 'storevalueOrderData.xlsx');
    }
}
