<?php

namespace App\Services;

use App\Models\MemberAccount;
use App\Models\MemberAccountLog;
use App\Models\Store\Account;
use App\Models\Store\AccountLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StoreAccountService extends BaseService
{
    /**
     * 改变余额
     */
    public static function changeBalance($storeId, $type, $value, $behavior = AccountLog::AMOUNT_BASE, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            switch ($type) {
                case 1:
                    $account->amount =  bcadd($account->amount, $value, 2);
                    $logType = 1;
                    break;
                case 2:
                    $account->amount =  bcsub($account->amount, $value, 2);
                    if ($account->balance < 0) {
                        Db::rollBack();
                        throw new BadRequestException('调整金额必须小于用户余额');
                    }
                    $logType = 0;
                    break;
                case 3:
                    if (bcsub($account->amount, $value, 2) == 0) {
                        Db::rollBack();
                        throw new BadRequestException('用户余额无需调整');
                    } elseif (bcsub($account->amount, $value, 2) > 0) {
                        $value = bcsub($account->amount, $value, 2);
                        $account->amount = bcsub($account->amount, $value);
                        $logType = 0;
                    } else {
                        $value = bcsub($account->amount, $value, 2) * -1;
                        $account->amount = bcadd($account->amount, $value);
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
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => $behavior,
                'channel' => AccountLog::CHANNEL_AMOUNT,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->amount,
                "adminId" => $adminId
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
    public static function withdrawalAmount($storeId, $type, $value, $behavior = AccountLog::WITHDRAWAL, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            switch ($type) {
                case 1:
                    $account->withdrawalAmount =  bcadd($account->withdrawalAmount, $value, 2);
                    $logType = 1;
                    break;
                case 2:
                    $account->withdrawalAmount =  bcsub($account->withdrawalAmount, $value, 2);
                    if ($account->withdrawalAmount < 0) {
                        Db::rollBack();
                        throw new BadRequestException('调整金额必须小于用户余额');
                    }
                    $logType = 0;
                    break;
                default:
                    Db::rollBack();
                    return false;
                    break;
            }
            $account->save();
            $account->refresh();
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => $behavior,
                'channel' => AccountLog::CHANNEL_WITHDRAWAL,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->withdrawalAmount,
                "adminId" => $adminId
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
     * 冻结金额
     */
    public static function freezeAmount($storeId, $type, $value, $behavior = AccountLog::WITHDRAWAL, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            switch ($type) {
                case 1:
                    $account->freezeAmount =  bcadd($account->freezeAmount, $value, 2);
                    $logType = 1;
                    break;
                case 2:
                    $account->freezeAmount =  bcsub($account->freezeAmount, $value, 2);
                    if ($account->freezeAmount < 0) {
                        Db::rollBack();
                        throw new BadRequestException('调整金额必须小于用户余额');
                    }
                    $logType = 0;
                    break;
                default:
                    Db::rollBack();
                    return false;
                    break;
            }
            $account->save();
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => $behavior,
                'channel' => AccountLog::CHANNEL_FREEZE,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->freezeAmount,
                "adminId" => $adminId
            ]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 退款
     */
    public static function refundApply($storeId, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            $account->freezeAmount =  bcsub($account->freezeAmount, $value, 2);
            if ($account->freezeAmount < 0) {
                Db::rollBack();
                throw new BadRequestException('调整金额必须小于冻结余额');
            }
            $account->refundOfAmount =  bcadd($account->refundOfAmount, $value, 2);
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::REFUND_APPLY,
                'channel' => AccountLog::CHANNEL_FREEZE,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->freezeAmount,
                "adminId" => $adminId
            ]);
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::REFUND_APPLY,
                'channel' => AccountLog::CHANNEL_REFUND,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->refundOfAmount,
                "adminId" => $adminId
            ]);
            if ($account->save()) {
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
     * 抽佣
     */
    public static function profitsharing($storeId, $value, $adminId = 0, $notes = '', $option = [])
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            $account->amount =  bcsub($account->amount, $value, 2);
            $log = AccountLog::create(array_merge([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::AMOUNT_PROFITSHARING,
                'channel' => AccountLog::CHANNEL_AMOUNT,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->amount,
                "adminId" => $adminId
            ], $option));
            if ($account->save()) {
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

    // public static function talentShowsharing($storeId, $value, $adminId = 0, $notes = '')
    // {
    //     try {
    //         DB::beginTransaction();
    //         $account = Account::where('storeId', $storeId)->first();
    //         if (empty($account)) {
    //             throw new BadRequestException('门店账户不存在');
    //         }
    //         $account->amount =  bcsub($account->amount, $value, 2);
    //         $log = AccountLog::create([
    //             'uniacid' => $account->uniacid,
    //             'storeId' => $account->storeId,
    //             'behavior' => AccountLog::AMOUNT_TALENTSHOW,
    //             'channel' => AccountLog::CHANNEL_AMOUNT,
    //             'type' => 0,
    //             'value' => $value,
    //             'notes' => $notes,
    //             'atLast' => $account->amount,
    //             "adminId" => $adminId
    //         ]);
    //         if ($account->save()) {
    //             Db::commit();
    //             return true;
    //         }
    //         Db::rollBack();
    //         return false;
    //     } catch (\Exception $e) {
    //         Db::rollBack();
    //         throw new BadRequestException($e->getMessage());
    //     }
    // }


    /**
     * 退款成功
     */
    public static function refund($storeId, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            $account->refundOfAmount =  bcsub($account->refundOfAmount, $value, 2);
            if ($account->refundOfAmount < 0) {
                Db::rollBack();
                throw new BadRequestException('调整金额必须小于退款中余额');
            }
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::REFUND_PASS,
                'channel' => AccountLog::CHANNEL_REFUND,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->refundOfAmount,
                "adminId" => $adminId
            ]);

            $account->refundAmount =  bcadd($account->refundAmount, $value, 2);
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::REFUND_PASS,
                'channel' => AccountLog::CHANNEL_REFUND,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->refundAmount,
                "adminId" => $adminId
            ]);
            if ($account->save()) {
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
     * 直接退款
     */
    public static function refundDirectly($storeId, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            $account->freezeAmount =  bcsub($account->freezeAmount, $value, 2);
//            if ($account->freezeAmount < 0) {
//                Db::rollBack();
//                throw new BadRequestException('冻结金额不足');
//            }
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::FREEZE_REFUND,
                'channel' => AccountLog::CHANNEL_FREEZE,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->freezeAmount,
                "adminId" => $adminId
            ]);

            $account->refundAmount =  bcadd($account->refundAmount, $value, 2);
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::REFUND_PASS,
                'channel' => AccountLog::CHANNEL_REFUND,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->refundAmount,
                "adminId" => $adminId
            ]);
            if ($account->save()) {
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
     *拒绝退款
     */
    public static function refundDown($storeId, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            $account->refundOfAmount =  bcsub($account->refundOfAmount, $value, 2);
            $account->freezeAmount =  bcadd($account->freezeAmount, $value, 2);
//            if ($account->refundOfAmount < 0) {
//                Db::rollBack();
//                throw new BadRequestException('退款中金额不足');
//            }
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::REFUND_DOWN,
                'channel' => AccountLog::CHANNEL_REFUND,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->refundOfAmount,
                "adminId" => $adminId
            ]);
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::REFUND_DOWN,
                'channel' => AccountLog::CHANNEL_FREEZE,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->freezeAmount,
                "adminId" => $adminId
            ]);
            if ($account->save()) {
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
     *出账
     */
    public static function orderOutAccount($storeId, $value, $behavior = AccountLog::AMOUNT_BASE, $adminId = 0, $notes = '', $option = [])
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                Log("AccountNull");
                throw new BadRequestException('门店账户不存在');
            }
            $account->freezeAmount =  bcsub($account->freezeAmount, $value, 2);
            $account->amount =  bcadd($account->amount, $value, 2);

            AccountLog::create(array_merge([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::AMOUNT_ORDER_BILL,
                'channel' => AccountLog::CHANNEL_FREEZE,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->freezeAmount,
                "adminId" => $adminId
            ], $option));

            AccountLog::create(array_merge([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::AMOUNT_ORDER_BILL,
                'channel' => AccountLog::CHANNEL_AMOUNT,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->amount,
                "adminId" => $adminId
            ], $option));
            if ($account->save()) {
                Db::commit();
                return true;
            }

            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 申请提现
     */
    public static function withdrawalApply($storeId, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            $account->amount =  bcsub($account->amount, $value, 2);
            if ($account->amount < 0) {
                Db::rollBack();
                throw new BadRequestException('可提现余额不足');
            }
            $account->withdrawalAmount =  bcadd($account->withdrawalAmount, $value, 2);
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::WITHDRAWAL,
                'channel' => AccountLog::CHANNEL_AMOUNT,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->amount,
                "adminId" => $adminId
            ]);
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::WITHDRAWAL,
                'channel' => AccountLog::CHANNEL_WITHDRAWAL,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->withdrawalAmount,
                "adminId" => $adminId
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
    public static function withdrawalPass($storeId, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            $account->withdrawalAmount =  bcsub($account->withdrawalAmount, $value, 2);
            if ($account->withdrawalAmount < 0) {
                Db::rollBack();
                throw new BadRequestException('可提现余额不足');
            }
            $account->withdrawalCompleteAmount =  bcadd($account->withdrawalCompleteAmount, $value, 2);
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::WITHDRAWAL_PASS,
                'channel' => AccountLog::CHANNEL_WITHDRAWAL,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->withdrawalAmount,
                "adminId" => $adminId
            ]);

            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => AccountLog::WITHDRAWAL_PASS,
                'channel' => AccountLog::CHANNEL_WITHDRAWAL_COMPLETE,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->withdrawalCompleteAmount,
                "adminId" => $adminId
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
     * 提现驳回
     */
    public static function withdrawalDown($storeId, $value, $behavior = AccountLog::WITHDRAWAL_REFUSE, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('门店账户不存在');
            }
            $account->withdrawalAmount =  bcsub($account->withdrawalAmount, $value, 2);
            if ($account->withdrawalAmount < 0) {
                Db::rollBack();
                throw new BadRequestException('可提现余额不足');
            }
            $account->amount =  bcadd($account->amount, $value, 2);
            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => $behavior,
                'channel' => AccountLog::CHANNEL_WITHDRAWAL,
                'type' => 0,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->withdrawalAmount,
                "adminId" => $adminId
            ]);

            $log = AccountLog::create([
                'uniacid' => $account->uniacid,
                'storeId' => $account->storeId,
                'behavior' => $behavior,
                'channel' => AccountLog::CHANNEL_AMOUNT,
                'type' => 1,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->amount,
                "adminId" => $adminId
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

    public static function subCanWithdrawalAmount($storeId, $money, $adminId = 0, $orderSn = null, $type=1)
    {
        try {
            $account = Account::where('storeId', $storeId)->first();
            if (empty($account)) {
                throw new BadRequestException('用户账户不存在');
            }
            if($type==1){
                $account->commission_amount =  bcsub($account->commission_amount, $money, 2);
            }else{
                $account->commission_amount =  bcadd($account->commission_amount, $money, 2);
            }
            $account->save();
            AccountLog::create([
                'uniacid' => $account->uniacid,
                'channel' => AccountLog::CHANNEL_REFUNDOK,
                'type' => 1,
                'value' => $money,
                'notes' => '门店分销奖励退款',
                "adminId" => $adminId,
                'behavior' =>  AccountLog::REFUND_PASS,
                'orderSn' => $orderSn,
                'storeId' => $storeId
            ]);
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
