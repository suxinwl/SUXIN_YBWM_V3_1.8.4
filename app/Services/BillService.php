<?php

namespace App\Services;

use App\Jobs\ProfitSharingJob;
use App\Models\Order\Bill;
use App\Models\Order\OrderIndex;
use App\Models\PayTemplate;
use App\Models\Store\Account;
use App\Models\Store\AccountLog;
use App\Services\Pay\WechatPay;
use App\Traits\ResourceTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class BillService
{
    public static function initBill($orderIndex)
    {
        try {
            DB::beginTransaction();
            $bill = new Bill();
            $bill->fill(collect($orderIndex)->toArray());
            $bill->fill(collect($orderIndex->subOrder)->toArray());
            $bill->orderMoney = $orderIndex->subOrder->money;
            $bill->init();
            $bill->save();
            if ($bill->payChannel != 2 && $bill->storeId > 0) {
                StoreAccountService::orderOutAccount($bill->storeId, $bill->orderMoney, AccountLog::AMOUNT_ORDER_BILL, 0, $bill->orderSn . "订单完结收入", [
                    'orderSn' => $orderIndex->orderSn,
                    'orderType' => $orderIndex->type
                ]);
                if ($bill->serverMoney > 0) {
                    StoreAccountService::profitsharing($bill->storeId, $bill->serverMoney, 0, $bill->orderSn . "订单抽佣", [
                        'orderSn' => $orderIndex->orderSn,
                        'orderType' => $orderIndex->type
                    ]);
                }
            }
            DB::commit();
            dispatch(new ProfitSharingJob($bill->id));
            return $bill;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return false;
        }
    }


    public static function profit_sharing($bill)
    {
        $config = PayTemplate::find($bill->payTempId);
        if ($config->channel == "weixin" && in_array($config->data['type'], [1, 2])) {
            WechatPay::profit_sharing($bill, $config);
        }
        return true;
    }

    public static function profit_query($bill)
    {
        $config = PayTemplate::find($bill->payTempId);
        if ($config->channel == "weixin" && in_array($config->data['type'], [1, 2])) {
            WechatPay::profit_query($bill, $config);
        }
        return true;
    }

    public static function unfreeze($bill)
    {
        $config = PayTemplate::find($bill->payTempId);
        if ($config->channel == "weixin" && in_array($config->data['type'], [1, 2])) {
            WechatPay::unfreeze($bill, $config);
        }
        return true;
    }
}
