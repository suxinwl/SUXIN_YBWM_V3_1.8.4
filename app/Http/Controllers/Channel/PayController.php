<?php

namespace App\Http\Controllers\Channel;

use App\Models\Order\OrderIndex;
use App\Models\PayConfig;
use App\Services\ConfigService;
use App\Services\MemberAccountService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\Pay\WechatPay;
use App\Services\PayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PayController extends ApiController
{

    public function show(Request $request, $orderSn)
    {
        $orderIndex = OrderIndex::unpaid()->where('orderSn', $orderSn)->first();
        if (empty($orderIndex)) {
            throw new BadRequestException('订单已取消或者已支付');
        }
        $list = PayConfig::where('channel', $this->channel())
            ->where('uniacid', $this->uniacid())
            ->where('state', 1)
            ->when($orderIndex->type != 2, function ($q) use ($orderIndex) {
                if ($orderIndex->subOrder->store->payChange == 0) {
                    return $q->where('storeId', 0);
                } else {
                    return $q->where('storeId', $orderIndex->storeId);
                }
            })
            ->when($orderIndex->type == 2, function ($q) {
                return $q->where('payType', '!=', 'balance');
            })
            ->when($orderIndex->subOrder->money == 0, function ($q) {
                return $q->where('payType', 'balance');
            })
            ->get();
        foreach ($list as $key => $v) {
            $v->setAppends([]);
            if ($v->payType == 'balance') {
                $v->balance = $orderIndex->balance;
            }
            $list[$key] = $v;
        }
        return $this->success([
            'expiredTime' => $orderIndex->subOrder->expiredTime ?? '',
            'expirationMinute' => $orderIndex->subOrder->expirationMinute ?? '',
            'orderId' => $orderIndex->subOrder->id,
            'orderType' => $orderIndex->type,
            'orderSn' => $orderIndex->orderSn,
            'payList' => $list,
            'money' => $orderIndex->subOrder->money
        ]);
    }

    public function store(Request $request)
    {
        if (empty($request->orderSn)) {
            throw new BadRequestException('订单已取消或者已支付');
        }
        $orderIndex = OrderIndex::unpaid()->where('orderSn', $request->orderSn)->first();
        if (empty($orderIndex)||!$orderIndex) {
            throw new BadRequestException('订单已取消或者已支付');
        }
        $order = [
            'takeOutNo' => $orderIndex->orderSn,
            'amount' => $orderIndex->subOrder->money,
            'desc' => "订单支付",
            'balance' => $orderIndex->balance,
            'attach' => json_encode(['takeOutNo' => $orderIndex->orderSn, 'channel' => $this->channel()]),
            'auth_code' => $request->aoth_code,
            'uniacid' => $orderIndex->uniacid,
            'storeId' => $orderIndex->storeId,
            'orderType' => $orderIndex->type,
            'storePay' => $orderIndex->subOrder->store->payChange
        ];
        $res = PayService::micropay($order, $this->channel());
        if (!$res) {
            return $this->failed('支付失败');
        }
        return $this->success($res, '支付成功');
    }

    public function jssdk()
    {
        $app = ChannelOpenWechat::officialAccount($this->uniacid());
        $url =  Request()->url;
        $config =  $app->jssdk->buildConfig([], false, false, false, [], $url);
        return $this->success($config);
    }
}
