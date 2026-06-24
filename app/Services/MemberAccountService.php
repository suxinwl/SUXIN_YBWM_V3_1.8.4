<?php

namespace App\Services;

use App\Enums\PayEnum;
use App\Events\MemberAccountEvent;
use App\Events\MemberRegisteredEvent;
use App\Listeners\Member\AccountListener;
use App\Models\Admin\Order;
use App\Models\Member\MemberQrCode;
use App\Models\Member\Vip;
use App\Models\MemberAccount;
use App\Models\MemberAccountLog;
use App\Models\Order\OrderIndex;
use App\Models\Order\OrderPay;
use App\Models\Order\PayLog;
use App\Models\RefundOrder;
use App\Models\StatisticsDay;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Services\LuckyWheelService;

class MemberAccountService extends BaseService
{
    /**
     * 改变用户余额
     */
    public static function changeBalance($userId, $type, $value, $behavior = MemberAccountLog::BASE, $adminId = 0, $notes = '', $orderSn = null)
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            try {
                $lock_key = 'lock_balance_' . $userId;
                $is_lock  = Redis::setnx($lock_key, 1); // 加锁
                if (!$is_lock) { // 获取锁权限
                    // 防止死锁
                    if (Redis::ttl($lock_key) == -1) {
                        Redis::expire($lock_key, 1);
                    }
                    $lock = true;
                    throw new BadRequestException('系统繁忙请稍后再试');
                }
            } catch (\Exception $e) {
                Redis::del($lock_key);
            }
            switch ($type) {
                case 1:
                    $account->balance =  bcadd($account->balance, $value, 2);
                    $logType = 1;
                    break;
                case 2:
                    $account->balance =  bcsub($account->balance, $value, 2);
                    if ($account->balance < 0) {
                        throw new BadRequestException('账户余额不足');
                    }
                    $logType = 0;
                    break;
                case 3:
                    if (bcsub($account->balance, $value, 2) == 0) {
                        Db::rollBack();
                        throw new BadRequestException('用户余额无需调整');
                    } elseif (bcsub($account->balance, $value, 2) > 0) {
                        $value = bcsub($account->balance, $value, 2);
                        $account->balance = bcsub($account->balance, $value);
                        $logType = 0;
                    } else {
                        $value = bcsub($account->balance, $value, 2) * -1;
                        $account->balance = bcadd($account->balance, $value);
                        $logType = 1;
                    }
                    break;
                default:
                    Db::rollBack();
                    return false;
                    break;
            }
            if ($type == 1 && in_array($behavior, MemberAccountLog::balanceList())) {
                $account->originalMoney =  bcadd($account->originalMoney, $value);
                StatisticsDay::where('storeId', 0)->where('uniacid', $account->uniacid)
                    ->where('day', date("Y-m-d", time()))
                    ->limit(1)
                    ->update([
                        'sysStoredValue' => DB::raw("sysStoredValue + $value"),
                        'storedValue' => DB::raw("storedValue + $value")
                    ]);
            }

            if ($type == 0) {
                StatisticsDay::where('storeId', 0)
                    ->where('uniacid', $account->uniacid)
                    ->where('day', date("Y-m-d", time()))
                    ->limit(1)
                    ->update(['sysSubStoredValue' => DB::raw("sysSubStoredValue + $value")]);
            }
            $account->save();
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'balance',
                'channel' => MemberAccountLog::CHANNEL_BALANCE,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->balance,
                "adminId" => $adminId,
                'behavior' =>  $behavior,
                'orderSn' => $orderSn,
                'storeId' => $account->member->storeId
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    public static function buyBalance($userId, $type, $value, $behavior = MemberAccountLog::BALANCE_BUY, $adminId = 0, $notes = '', $orderSn = null)
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            $account->balance =  bcadd($account->balance, $value, 2);
            $logType = 1;
            if ($type == 1 && in_array($behavior, MemberAccountLog::balanceList())) {
                $account->originalMoney =  bcadd($account->originalMoney, $value);
                StatisticsDay::where('storeId', 0)->where('uniacid', $account->uniacid)
                    ->where('day', date("Y-m-d", time()))
                    ->limit(1)
                    ->update([
                        'storedValueCount' => DB::raw("storedValueCount + 1"),
                        'miniStoredValueCount' => DB::raw("miniStoredValueCount + 1"),
                        'miniStoredValue' => DB::raw("miniStoredValue + $value"),
                        'storedValueCapital' => DB::raw("storedValueCapital + $value"),
                        'storedValue' => DB::raw("storedValue + $value")
                    ]);
            }
            $account->save();
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'balance',
                'channel' => MemberAccountLog::CHANNEL_BALANCE,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->balance,
                "adminId" => $adminId,
                'behavior' =>  $behavior,
                'orderSn' => $orderSn,
                'storeId' => $account->member->storeId
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 赠送余额
     */
    public static function GiveChange($userId, $storeId, $value, $behavior = MemberAccountLog::BALANCE_GIVE, $adminId = 0, $notes = '', $orderSn = null)
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            $account->balance =  bcadd($account->balance, $value, 2);
            $account->giveMoney =  bcadd($account->giveMoney, $value, 2);
            $logType = 1;
            $account->save();
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'balance',
                'channel' => MemberAccountLog::BALANCE_GIVE,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->balance,
                "adminId" => $adminId,
                'behavior' =>  $behavior,
                'orderSn' => $orderSn,
                'storeId' => $account->member->storeId
            ]);
            Db::commit();
            StatisticsDay::where(function ($q) use ($storeId) {
                return $q->where("storeId", 0)->orWhere("storeId", $storeId);
            })->where('uniacid', $account->uniacid)
                ->where('day', date("Y-m-d", time()))
                ->limit(1)
                ->update([
                    'storedValue' => DB::raw("storedValueGive + $value"),
                    'storedValueGive' => DB::raw("storedValueGive + $value")
                ]);
            return true;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 改变用户积分
     */
    public static function changeIntegral($userId, $type, $value, $behavior = MemberAccountLog::BASE, $adminId = 0, $notes = '', $orderSn = null)
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            switch ($type) {
                case 1:
                    $account->integral =  bcadd($account->integral, $value, 0);
                    $logType = 1;
                    break;
                case 2:
                    $account->integral =  bcsub($account->integral, $value, 0);
                    if ($account->integral < 0) {
                        $account->integral = 0;
                    }
                    $logType = 0;
                    break;
                case 3:
                    if (bcsub($account->integral, $value, 0) == 0) {
                        Db::rollBack();
                        throw new BadRequestException('用户积分无需调整');
                    } elseif (bcsub($account->integral, $value, 0) > 0) {
                        $value = bcsub($account->integral, $value, 0);
                        $account->integral = bcsub($account->integral, $value);
                        $logType = 0;
                    } else {
                        $value = bcsub($account->integral, $value, 0) * -1;
                        $account->integral = bcadd($account->integral, $value);
                        $logType = 1;
                    }
                    break;
                default:
                    Db::rollBack();
                    return false;
                    break;
            }
            $account->save();
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'integral',
                'channel' => MemberAccountLog::CHANNEL_INTEGRAL,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->integral,
                "adminId" => $adminId,
                'behavior' => $behavior,
                'orderSn' => $orderSn,
                'storeId' => $account->member->storeId
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 改变用户成长值
     */
    public static function changeExp($userId, $type, $value, $behavior = MemberAccountLog::BASE, $adminId = 0, $notes = '', $orderSn = null)
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            switch ($type) {
                case 1:
                    $account->exp =  bcadd($account->exp, $value, 0);
                    $logType = 1;
                    break;
                case 2:
                    $account->exp =  bcsub($account->exp, $value, 0);
                    if ($account->integral < 0) {
                        $account->integral = 0;
                    }
                    $logType = 0;
                    break;
                case 3:
                    if (bcsub($account->exp, $value, 0) == 0) {
                        Db::rollBack();
                        throw new BadRequestException('用户成长值无需调整');
                    } elseif (bcsub($account->exp, $value, 0) > 0) {
                        $value = bcsub($account->exp, $value, 0);
                        $account->exp = bcsub($account->exp, $value);
                        $logType = 0;
                    } else {
                        $value = bcsub($account->exp, $value, 0) * -1;
                        $account->exp = bcadd($account->exp, $value);
                        $logType = 1;
                    }
                    break;
                default:
                    Db::rollBack();
                    return false;
                    break;
            }
            $account->save();
            $vipChange = '';
            if ($account->member->vip->exp < $account->exp) {
                $vip = Vip::where('uniacid', $account->uniacid)
                    ->where('storeId', $account->member->storeId)
                    ->where('exp', "<=", $account->exp)
                    ->orderBy('exp', 'desc')
                    ->first();
                if ($account->member && $vip && $vip->id != $account->member->vipId) {
                    $vipChange =  "{$account->member->vip->name} 升级至 $vip->name";
                    $account->member->vipId = $vip->id;
                    $account->member->save();
                    $account->member->refresh();
                    Event(new MemberRegisteredEvent($account->member));
                    Event(new MemberAccountEvent($account->member, 'vipChange'));
                }
            } elseif ($account->member->vip->exp > $account->exp) {
                $vip = Vip::where('uniacid', $account->uniacid)->where('exp', "<=", $account->exp)->orderBy('exp', 'desc')->first();
                $vipChange =  "{$account->member->vip->name} 降级至 $vip->name";
                $account->member->vipId = $vip->id;
                $account->member->save();
            }
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'exp',
                'channel' => MemberAccountLog::CHANNEL_EXP,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->exp,
                "adminId" => $adminId,
                'vipChange' => $vipChange,
                'behavior' => $behavior,
                'orderSn' => $orderSn,
                'storeId' => $account->member->storeId
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }


    /**
     * 分销佣金
     */
    public static function partnerBill($userId, $money, $adminId = 0, $orderSn = null, $notes)
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            $account->freezeAmount = bcsub($account->freezeAmount, $money, 2);
            $account->canWithdrawalAmount =  bcadd($account->canWithdrawalAmount, $money, 2);
            $account->save();
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'balance',
                'channel' => MemberAccountLog::CHANNEL_CANWITHDRAWALAMOUNT,
                'type' => 1,
                'value' => $money,
                'notes' => $notes,
                'atLast' => $account->canWithdrawalAmount,
                "adminId" => $adminId,
                'orderSn' => $orderSn,
                'storeId' => $account->member->storeId,
                'behavior' => MemberAccountLog::CANWITHDRAWALAMOUNT_PARTNER_REWARD
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }
    }

    public static function subCanWithdrawalAmount($userId, $money, $adminId = 0, $orderSn = null, $notes)
    {
        try {
            //DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            $account->canWithdrawalAmount =  bcsub($account->canWithdrawalAmount, $money, 2);
            $account->save();
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'balance',
                'channel' => MemberAccountLog::CHANNEL_CANWITHDRAWALAMOUNT,
                'type' => 1,
                'value' => $money,
                'notes' => $notes,
                'atLast' => $account->canWithdrawalAmount,
                "adminId" => $adminId,
                'orderSn' => $orderSn,
                'storeId' => $account->member->storeId,
                'behavior' => MemberAccountLog::CANWITHDRAWALAMOUNT_PARTNER_REWARD
            ]);
            if ($log) {
                //Db::commit();
                return true;
            }
            //Db::rollBack();
            return false;
        } catch (\Exception $e) {
            //Db::rollBack();
            return false;
        }
    }

    /**
     * 改变用户可提现金额
     */
    public static function changeCanWithdrawalAmount($userId, $type, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            switch ($type) {
                case 1:
                    $account->canWithdrawalAmount =  bcadd($account->canWithdrawalAmount, $value, 2);
                    $logType = 1;
                    break;
                case 2:
                    $account->canWithdrawalAmount =  bcsub($account->canWithdrawalAmount, $value, 2);
                    if ($account->canWithdrawalAmount < 0) {
                        Db::rollBack();
                        throw new BadRequestException('调整金额必须小于可提现金额');
                    }
                    $logType = 0;
                    break;
                case 3:
                    if (bcsub($account->canWithdrawalAmount, $value, 2) == 0) {
                        Db::rollBack();
                        throw new BadRequestException('无需调整');
                    } elseif (bcsub($account->canWithdrawalAmount, $value, 2) > 0) {
                        $value = bcsub($account->canWithdrawalAmount, $value, 2);
                        $account->canWithdrawalAmount = bcsub($account->canWithdrawalAmount, $value);
                        $logType = 0;
                    } else {
                        $value = bcsub($account->canWithdrawalAmount, $value, 2) * -1;
                        $account->canWithdrawalAmount = bcadd($account->canWithdrawalAmount, $value);
                        $logType = 1;
                    }
                    break;
                default:
                    Db::rollBack();
                    return false;
                    break;
            }
            $account->save();
            $account->refresh();
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'balance',
                'channel' => MemberAccountLog::CHANNEL_CANWITHDRAWALAMOUNT,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->balance,
                "adminId" => $adminId,
                'storeId' => $account->member->storeId,
                'behavior' => MemberAccountLog::BASE,
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 提现金额
     */
    public static function canWithdrawalAmount($userId, $type = 1, $money, $behavior = 0, $adminId, $orderSn, $notes)
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            $account->canWithdrawalAmount =  bcadd($account->canWithdrawalAmount, $money, 2);
            $account->save();
            $account->refresh();
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'balance',
                'channel' => MemberAccountLog::FREEZEAMOUNT_REWARD_REFUND,
                'type' => 1,
                'value' => $money,
                'notes' => $notes,
                'atLast' => $account->canWithdrawalAmount,
                "adminId" => $adminId,
                'behavior' => $behavior,
                'storeId' => $account->member->storeId,
                'orderSn' => $orderSn,
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }
    }


    /**
     * 冻结金额
     */
    public static function freezeAmount($userId, $type = 1, $money, $behavior = 0, $adminId = 0, $orderSn, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            if ($type == 1) {
                $account->freezeAmount =  bcadd($account->freezeAmount, $money, 2);
            } else {
                $type = 0;
                $account->freezeAmount =  bcsub($account->freezeAmount, $money, 2);
            }
            $account->save();
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'balance',
                'channel' => MemberAccountLog::CHANNEL_FREEZEAMOUNT,
                'type' => $type,
                'value' => $money,
                'notes' => $notes,
                'atLast' => $account->freezeAmount,
                "adminId" => $adminId,
                'behavior' => $behavior,
                'orderSn' => $orderSn,
                'storeId' => $account->member->storeId
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }
    }

    /**
     * 提现驳回
     */
    public static function withdrawalDown($userId, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            $account->withdrawalAmount =  bcsub($account->withdrawalAmount, $value, 2);
            $account->canWithdrawalAmount =  bcadd($account->canWithdrawalAmount, $value, 2);
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'canWithdrawalAmount',
                'behavior' => MemberAccountLog::WITHDRAWAL_ERROR,
                'channel' => MemberAccountLog::CHANNEL_WITHDRAWALAMOUNT,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->withdrawalAmount,
                "adminId" => $adminId,
                'storeId' => $account->member->storeId
            ]);

            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'behavior' => MemberAccountLog::WITHDRAWAL_ERROR,
                'channel' => MemberAccountLog::CHANNEL_CANWITHDRAWALAMOUNT,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->withdrawalAmount,
                "adminId" => $adminId,
                'storeId' => $account->member->storeId
            ]);
            if ($account->save()) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }
    }

    /**
     * 申请提现
     */
    public static function withdrawalApply($userId, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            $account->canWithdrawalAmount =  bcsub($account->canWithdrawalAmount, $value, 2);
            if ($account->canWithdrawalAmount < 0) {
                Db::rollBack();
                throw new BadRequestException('可提现余额不足');
            }
            $account->withdrawalAmount =  bcadd($account->withdrawalAmount, $value, 2);
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'behavior' => MemberAccountLog::WITHDRAWAL,
                'channel' => MemberAccountLog::CHANNEL_CANWITHDRAWALAMOUNT,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->canWithdrawalAmount,
                "adminId" => $adminId,
                'storeId' => $account->member->storeId
            ]);
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'behavior' => MemberAccountLog::WITHDRAWAL,
                'channel' => MemberAccountLog::CHANNEL_WITHDRAWALAMOUNT,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->withdrawalAmount,
                "adminId" => $adminId,
                'storeId' => $account->member->storeId
            ]);
            if ($account->save()) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }
    }

    /**
     * 提现成功
     */
    public static function withdrawalPass($userId, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            $account->withdrawalAmount =  bcsub($account->withdrawalAmount, $value, 2);
            if ($account->withdrawalAmount < 0) {
                Db::rollBack();
                throw new BadRequestException('可提现余额不足');
            }
            $account->withdrawalCompleteAmount =  bcadd($account->withdrawalCompleteAmount, $value, 2);
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'behavior' => MemberAccountLog::WITHDRAWAL_OK,
                'channel' => MemberAccountLog::CHANNEL_WITHDRAWALAMOUNT,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->withdrawalAmount,
                "adminId" => $adminId,
                'storeId' => $account->member->storeId
            ]);

            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'behavior' => MemberAccountLog::WITHDRAWAL_OK,
                'channel' => MemberAccountLog::CHANNEL_WITHDRAWALCOMPLETEAMOUNT,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->withdrawalCompleteAmount,
                "adminId" => $adminId,
                'storeId' => $account->member->storeId
            ]);
            if ($account->save()) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }
    }
    /**
     * 余额支付
     */
    public static function pay($orderSn = '', $payId, $userId)
    {
        try {
            DB::beginTransaction();
            $order = OrderIndex::unpaid()->where('orderSn', $orderSn)->first();
            if (empty($order)) {
                throw new BadRequestException('订单已取消或者已支付');
            }

            $userId = $userId ?? $order->userId;
            if ($order->subOrder->money > 0) {
                $payState = self::changeBalance($userId ?? $order->userId, 2, $order->subOrder->money, MemberAccountLog::BALANCE_ORDER_PAY, $order->userId, $order->orderSn . "订单支付");
                if (!$payState) {
                    Db::rollBack();
                    return false;
                }
            }
            $payChannel = $order->store->isolate == 1 ? 2 : 1;
            switch ($order->type) {
                case 1:
                    $notifyState = OrderNotifyService::takeout(['trade_type' => 0, 'payChannel' => $payChannel], $order->orderSn, $payId);
                    break;
                case 3:
                    $notifyState = OrderNotifyService::personPay(['trade_type' => 0, 'payChannel' => $payChannel], $order->orderSn, $payId);
                    break;
                case 4:
                    $notifyState = OrderNotifyService::inStore(['trade_type' => 0, 'userId' => $userId, 'payChannel' => $payChannel], $order->orderSn, $payId, $userId);
                    break;
                case 5:
                    $notifyState = OrderNotifyService::pointsMail(['trade_type' => 0, 'userId' => $userId, 'payChannel' => $payChannel], $order->orderSn, $payId, $userId);
                    break;
                case 6:
                    $notifyState = OrderNotifyService::couponPack(['trade_type' => 0, 'userId' => $userId, 'payChannel' => $payChannel], $order->orderSn, $payId, $userId);
                    break;
                case 7:
                    $notifyState = OrderNotifyService::tableReserve(['trade_type' => 0, 'userId' => $userId, 'payChannel' => $payChannel], $order->orderSn, $payId, $userId);
                    break;
                case 8:
                    $notifyState = OrderNotifyService::equityCard(['trade_type' => 0, 'userId' => $userId, 'payChannel' => $payChannel], $order->orderSn, $payId, $userId);
                    break;
                default:
                    $notifyState = false;
            }
            if ($notifyState) {
                //增加大转盘次数
                LuckyWheelService::check($order);

                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    public static function refund($order)
    {
        try {
            DB::beginTransaction();
            $orderIndex = OrderIndex::where('orderSn', $order['takeOutNo'])->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('订单状态不正确');
            };
            $payState = self::changeBalance($orderIndex->userId, 1, $order['refund_amount'], MemberAccountLog::BALANCE_ORDER_REFUND, $orderIndex->userId, $order['takeOutNo'] . "订单退款");
            if (!$payState) {
                Db::rollBack();
                return false;
            }
            RefundOrder::create([
                'takeOutNo' => $orderIndex->orderSn,
                'refundNo' => getTakeOutNo(),
                'state' => 1,
                'data' => ['notes' => '余额支付退款']
            ]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }
    }

    public static function giveRefund($userId, $type, $value, $behavior = MemberAccountLog::BASE, $adminId = 0, $notes = '', $orderSn = null)
    {
        try {
            DB::beginTransaction();
            $account = MemberAccount::where('userId', $userId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            try {
                $lock_key = 'lock_balance_' . $userId;
                $is_lock  = Redis::setnx($lock_key, 1); // 加锁
                if (!$is_lock) { // 获取锁权限
                    // 防止死锁
                    if (Redis::ttl($lock_key) == -1) {
                        Redis::expire($lock_key, 1);
                    }
                    $lock = true;
                    throw new BadRequestException('系统繁忙请稍后再试');
                }
            } catch (\Exception $e) {
                Redis::del($lock_key);
            }
            $account->balance =  bcsub($account->balance, $value, 2);
            if ($account->balance < 0) {
                $value =  $account->balance;
                $account->balance = 0;
            }
            $logType = 0;
            $account->save();
            $log = MemberAccountLog::create([
                'uniacid' => $account->uniacid,
                'userId' => $account->userId,
                'cat' => 'balance',
                'channel' => MemberAccountLog::CHANNEL_BALANCE,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->balance,
                "adminId" => $adminId,
                'behavior' =>  $behavior,
                'orderSn' => $orderSn,
                'storeId' => $account->member->storeId
            ]);
            if ($type == 0) {
                StatisticsDay::where('storeId', 0)->where('uniacid', $account->uniacid)
                    ->where('day', date("Y-m-d", time()))
                    ->update(['sysStoredValue' => DB::raw("sysSubStoredValue + $value")]);
            }
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 余额支付
     */
    public static function micropay($order)
    {
        try {
            DB::beginTransaction();
            $memberQrcode = MemberQrCode::where('qrcode', $order['auth_code'])
                ->first();
            if ($memberQrcode->userId != $order['userId']) {
                throw new BadRequestException('付款码识别错误');
            }
            if (empty($memberQrcode) || strtotime($memberQrcode->expired) < time()) {
                throw new BadRequestException('付款码已失效');
            }
            if ($order['amount'] > $order['balance']) {
                throw new BadRequestException('账户余额不足');
            }
            if ($order['amount'] > 0) {
                $payState = self::changeBalance($memberQrcode->userId, 2, $order['amount'], MemberAccountLog::BALANCE_ORDER_PAY, $memberQrcode->userId, $order['takeOutNo'] . "订单支付");
                if (!$payState) {
                    Db::rollBack();
                    return false;
                }
            }
            Db::commit();
            return $memberQrcode->userId;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 余额支付
     */
    public static function pointsPay($orderSn = '', $userId)
    {
        $lockKey='pointsPay'.$orderSn;
        $isLocked = Cache::lock($lockKey, 1);
        if (!$isLocked->get()) {
            throw new BadRequestException('有其它操作正在进行,请稍后再试');
        }
        try {
            DB::beginTransaction();
            $order = OrderIndex::unpaid()->where('orderSn', $orderSn)->first();
            if (empty($order)) {
                throw new BadRequestException('订单已取消或者已支付');
            }
            $userId = $userId ?? $order->userId;
            if ($order->subOrder->points > 0) {
                $payState = self::changeIntegral($userId ?? $order->userId, 2, $order->subOrder->points, MemberAccountLog::INTEGRAL_ORDER_PAY, $order->userId, $order->orderSn . "积分兑换");
                if (!$payState) {
                    Db::rollBack();
                    return false;
                }
                OrderPay::create([
                    'orderSn' => $order->orderSn,
                    'uniaicd' => $order->uniacid,
                    'prentOrderSn' => $order->orderSn,
                    'thirdNo' => null,
                    'payType' => PayEnum::POINTS,
                    'payTempId' => 0,
                    'profit_sharing' => 0,
                    'payChannel' => 1,
                    'money' => $order->subOrder->points,
                    'state' => 1
                ]);
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }
    public static function cash($orderSn = '', $payId)
    {
        try {
            DB::beginTransaction();
            $order = OrderIndex::unpaid()->where('orderSn', $orderSn)->first();
            if (empty($order)) {
                throw new BadRequestException('订单已取消或者已支付');
            }
            $payChannel = $order->store->isolate == 1 ? 2 : 1;
            if ($order->type == 1) {
                $notifyState = OrderNotifyService::cash(['trade_type' => 6, 'payChannel' => $payChannel], $order->orderSn, $payId);
            } elseif ($order->type == 4) {
                $orderInfo = [
                    'takeOutNo' => $order->orderSn,
                    'orderSn' => $order->orderSn,
                    'amount' => $order->money,
                    'payTempId' => 0,
                    'payChannel' => $payChannel,
                    'trade_type' => 6
                ];
                $notifyState = OrderNotifyService::inStore($orderInfo, $orderInfo['takeOutNo'], $orderInfo['payTempId'], $order->userId);
            }
            if ($notifyState) {
                LuckyWheelService::check($order);
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

}
