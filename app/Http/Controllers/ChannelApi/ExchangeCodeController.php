<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Controllers\ChannelApi\ApiController;
use App\Models\ExchangeCode\ExchangeCode;
use App\Models\ExchangeCode\ExchangeCodeReceive;
use App\Models\MemberAccountLog;
use App\Services\CouponService;
use App\Services\MemberAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class ExchangeCodeController extends ApiController
{

    public function coupon(Request $request)
    {
        try {
            DB::beginTransaction();
            $uniacid = $this->uniacid();
            $model = ExchangeCodeReceive::with(['exchangeCode'])
                ->where('uniacid', $this->uniacid())
                ->whereHas('exchangeCode', function ($q) use ($uniacid) {
                    return $q->where('uniacid', $uniacid);
                })->where('sn', $request->sn)->first();
            if (empty($model)) {
                return $this->failed('兑换码不存在或已失效');
            }
            if ($model->state == 2 &&  $model->exchangeCode->type == 1) {
                return $this->failed('兑换码已被兑换');
            }
            if ($model->exchangeCode->endTime < date("Y-m-d H:i:s")) {
                return $this->failed('该兑换活动已结束');
            }
            if ($model->inventory <= 0) {
                throw new BadRequestException('活动兑换已达上限');
            }
            if ($model->exchangeCode->limitSwitct == 2 && $model->userLimit >= $model->exchangeCode->limit) {
                throw new BadRequestException('您的兑换次数已达上限');
            }
            if ($model->exchangeCode->limitDaySwitct == 2 && $model->userDayLimit >= $model->exchangeCode->limitDay) {
                throw new BadRequestException('您今日兑换次数已达上限');
            }
            if ($model->exchangeCode->giveType == 1) {
                CouponService::issue($model->exchangeCode->couponGive, $this->userId(), 12);
            }
            if ($model->exchangeCode->giveType == 2) {
                MemberAccountService::GiveChange($this->userId(), 0, $model->exchangeCode->balance, MemberAccountLog::BALANCE_EXCHANGECODE, 0, "兑换活动赠送{$model->balance}余额");
            }
            if ($model->type == 1) {
                $model->userId = $this->userId();
                $model->state = 2;
                $model->save();
            }
            if ($model->type == 2) {
                $model = ExchangeCodeReceive::create([
                    'uniacid' => $model->uniacid,
                    'userId' => $this->userId(),
                    'exchangeCodeId' => $model->exchangeCodeId,
                    'type' => $model->type,
                    'sn' => CouponRandInt(10),
                    'state' => 2,
                    'display' => 1
                ]);
            }
            $limitKey = "exchangCode:userlimit:{$model->exchangeCodeId}{$model->userId}";
            $dayLimitKey = "exchangCode:userDaylimit:{$model->exchangeCodeId}" . date("Ymd") . ":{$model->userId}";
            Cache::set($dayLimitKey, (Cache::get($dayLimitKey, 0) + 1));
            Cache::set($limitKey, (Cache::get($limitKey, 0) + 1));
            DB::commit();
            return $this->success(null, '兑换成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    public function balance(Request $request)
    {
        DB::beginTransaction();
        try {
            $uniacid = $this->uniacid();
            $model = ExchangeCodeReceive::with(['exchangeCode'])
                ->where('uniacid', $this->uniacid())
                ->whereHas('exchangeCode', function ($q) use ($uniacid) {
                    return $q->where('uniacid', $uniacid);
                })->where('sn', $request->sn)->first();
            if (empty($model)) {
                return $this->failed('兑换码不存在或已失效');
            }
            if ($model->state == 2 &&  $model->exchangeCode->type == 1) {
                return $this->failed('兑换码已被兑换');
            }
            if ($model->exchangeCode->endTime < date("Y-m-d H:i:s")) {
                return $this->failed('该兑换活动已结束');
            }
            if ($model->exchangeCode->num <= 0) {
                throw new BadRequestException('活动兑换已达上限');
            }
            if ($model->exchangeCode->limitSwitct == 2 && $model->userLimit >= $model->exchangeCode->limit) {
                throw new BadRequestException('您的兑换次数已达上限');
            }
            if ($model->exchangeCode->limitDaySwitct == 2 && $model->userDayLimit >= $model->exchangeCode->limitDay) {
                throw new BadRequestException('您今日兑换次数已达上限');
            }
            if ($model->exchangeCode->giveType == 1) {
                CouponService::issue($model->exchangeCode->couponGive, $this->userId(), 12);
            }
            if ($model->exchangeCode->giveType == 2) {
                MemberAccountService::GiveChange($this->userId(), 0, $model->exchangeCode->balance, MemberAccountLog::BALANCE_EXCHANGECODE, 0, "兑换活动赠送{$model->balance}余额");
            }
            if ($model->type == 1) {
                $model->userId = $this->userId();
                $model->state = 2;
                $model->save();
            }
            if ($model->type == 2) {
                $model = ExchangeCodeReceive::create([
                    'uniacid' => $model->uniacid,
                    'userId' => $this->userId(),
                    'exchangeCodeId' => $model->exchangeCodeId,
                    'type' => $model->type,
                    'sn' => CouponRandInt(10),
                    'state' => 2,
                    'display' => 1
                ]);
            }
            $limitKey = "exchangCode:userlimit:{$model->exchangeCodeId}{$model->userId}";
            $dayLimitKey = "exchangCode:userDaylimit:{$model->exchangeCodeId}" . date("Ymd") . ":{$model->userId}";
            Cache::set($dayLimitKey, (Cache::get($dayLimitKey, 0) + 1));
            Cache::set($limitKey, (Cache::get($limitKey, 0) + 1));
            DB::commit();
            return $this->success(null, '兑换成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
