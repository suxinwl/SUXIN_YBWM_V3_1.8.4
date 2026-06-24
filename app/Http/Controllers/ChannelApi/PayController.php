<?php

namespace App\Http\Controllers\ChannelApi;

use App\Models\Order\OrderIndex;
use App\Models\Order\PayLog;
use App\Models\PayConfig;
use App\Models\PointsMall;
use App\Models\PointsMall\Order;
use App\Services\ConfigService;
use App\Services\MemberAccountService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\Pay\WechatPay;
use App\Services\PayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Redis;
use App\Models\MemberAccountLog;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Yansongda\Pay\Pay;
use App\Models\MemberAccount;

class PayController extends ApiController
{

    public function show(Request $request, $orderSn)
    {
        $orderIndex = OrderIndex::unpaid()->where('orderSn', $orderSn)->first();
        if (empty($orderIndex)) {
            throw new BadRequestException('订单已取消或者已支付');
        }
        $orderIndex->userId = $this->userId();
        $channel = $this->channel();
        $score = $this->appType();
        $isolate = $this->isolate();
        $list = PayConfig::where(function ($q) use ($channel, $score) {
            if (in_array($score, [3, 12])) {
                return $q->whereIn('payType', ["alipay", "balance"]);
            } elseif (in_array($score, [1, 2])) {
                return $q->whereIn('payType', ["weixin", "balance"]);
            } else {
                return $q->where('channel', $channel);
            }
        })
            ->where('uniacid', $this->uniacid())
            ->where('state', 1)
            ->when($orderIndex->type != 2, function ($q) use ($orderIndex, $isolate) {
                if (($orderIndex->subOrder->store->payChange == 0 || in_array($orderIndex->type, [6, 7, 8])) && $isolate == 0) {
                    return $q->where('storeId', 0);
                } else {
                    return $q->where('storeId', $orderIndex->storeId);
                }
            })
            ->when($orderIndex->type == 2, function ($q) {
                return $q->where('payType', '!=', 'balance')
                    ->where('storeId', 0);
            })
            ->when($orderIndex->subOrder->money == 0, function ($q) {
                return $q->where('payType', 'balance');
            })
            ->get();
        foreach ($list as $key => $v) {
            $isDefault = $v->isDefault;
            $v->setAppends([]);
            if ($v->payType == 'balance') {
                $v->balance = $orderIndex->balance;
            }
            $list[$key] = $v;
        }
        if ($isDefault == 0) {
            $list = collect($list)->map(function ($item, $key) {
                $item = $item->toArray();
                if ($key == 0) {
                    $item['isDefault'] = 1;
                }
                return $item;
            });
        }
        $money=$orderIndex->subOrder->money;
        return $this->success([
            'expiredTime' => $orderIndex->subOrder->expiredTime ?? '',
            'expirationMinute' => $orderIndex->subOrder->expirationMinute ?? '',
            'orderId' => $orderIndex->subOrder->id,
            'orderType' => $orderIndex->type,
            'orderSn' => $orderIndex->orderSn,
            'payList' => $list,
            'money' => $money
        ]);
    }

    public function store(Request $request)
    {
        try {
            if($request->orderType==5){
                $order = Order::where('orderSn',$request->orderSn)->first();
                if($order->state==1){
                    $model =  MemberAccount::where('uniacid', $this->uniacid())->where('userId', $this->userId())->first();
                    if($order->points>$model->integral){
                        return $this->failed('积分不足');
                    }
                    $list = PointsMall::where('id', $order->goods['id'])->first();
                    if($list->stock<1){
                        return $this->failed('兑换商品库存不足');
                    }
                }
            }
            if (empty($request->orderSn)) {
                throw new BadRequestException('订单已取消或者已支付');
            }
            $orderIndex = OrderIndex::unpaid()->where('orderSn', $request->orderSn)->first();
            if ($orderIndex->userId == 0) {
                $orderIndex->userId = $this->userId();
            }
            if (empty($orderIndex)||!$orderIndex) {
                throw new BadRequestException('订单已取消或者已支付');
            }

            $lock_key = 'order_pay' . $orderIndex->orderSn;
            $is_lock  = Cache::lock($lock_key, 5)->get();
            if (!$is_lock) { // 获取锁权限
                // 防止死锁
                throw new BadRequestException('该订单正在支付中');
            }
            // if ($orderIndex->type == 4 && $orderIndex->subOrder->diningType == 4) {
            // } else {
            //     $payLog = $orderIndex->orderSn;
            // }
            $payLog = getTakeOutNo();
            PayLog::updateOrCreate(['orderSn' => $orderIndex->orderSn], [
                'uniacid' => $orderIndex->uniacid,
                'storeId' => $orderIndex->storeId,
                'orderSn' => $orderIndex->orderSn,
                'paySn' => $payLog
            ]);
            $row=PayLog::where('orderSn',$orderIndex->orderSn)->where('paySn',$payLog)->first();
            if(empty($row)||!$row){
                throw new BadRequestException('订单异常,请重新发起支付');
            }
            $order = [
                'uniacid' => $orderIndex->uniacid,
                'orderSn' => $orderIndex->orderSn,
                'takeOutNo' => $payLog,
                'openid' => $this->user()->getOpenId(),
                'amount' => $orderIndex->subOrder->money,
                'desc' => "订单支付",
                'balance' => $orderIndex->balance,
                'userId' =>  $this->userId(),
                'attach' => json_encode(['takeOutNo' => $orderIndex->orderSn, 'userId' => $this->userId()])
            ];
            if ($orderIndex->subOrder->money == 0) {
                if($orderIndex->subOrder->points){
                    $res = MemberAccountService::changeIntegral($orderIndex->userId, 2, $orderIndex->subOrder->points, MemberAccountLog::BASE, 0, '积分商城兑换商品');

                }

                $res =  MemberAccountService::pay($order['orderSn'], 0, Request()->payId, $order['userId']);
            } else {
                $res = PayService::pay($order, $this->uniacid(), Request()->payId, $this->channel());
            }
            optional($is_lock)->release();
            if (!$res) {
                return $this->failed('支付失败');
            }
            return $this->success($res, '支付成功');
        } catch (\Exception $e) {
            optional($is_lock)->release();
            Log::error($e->getMessage());
            return $this->failed($e->getMessage());
        }
    }

    public function query(Request $request, $orderSn)
    {
        $orderIndex = DB::table('order_index')->where('orderSn', $orderSn)->first();
        return $this->success($orderIndex);
    }

    public function jssdk()
    {
        $app = ChannelOpenWechat::officialAccount($this->uniacid());
        $url =  Request()->url;
        $config =  $app->jssdk->buildConfig([], false, false, false, [], $url);
        return $this->success($config);
    }
}
