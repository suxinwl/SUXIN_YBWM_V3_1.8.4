<?php

namespace App\Services;

use App\Models\MemberAccount;
use App\Models\MemberAccountLog;
use App\Models\SmsAccount;
use App\Models\SmsAccountLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SmsAccountService extends BaseService
{
    /**
     * 系统赠送
     */
    public static function change($uniacid, $type, $value, $adminId = 0, $notes = '', $orderSn = '')
    {
        try {
            DB::beginTransaction();
            $account = SmsAccount::where('uniacid', $uniacid)->first();
            if (empty($account)) {
                throw new BadRequestException('账户不存在');
            }
            switch ($type) {
                case 1:
                    $account->count =  bcadd($account->count, $value, 0);
                    $logType = 1;
                    break;
                case 2:
                    $account->count =  bcsub($account->count, $value, 0);
                    if ($account->balance < 0) {
                        Db::rollBack();
                        throw new BadRequestException('调整数值必须小于剩余条数');
                    }
                    $logType = 0;
                    break;
                case 3:
                    if (bcsub($account->count, $value, 0) == 0) {
                        Db::rollBack();
                        throw new BadRequestException('短信余额无需调整');
                    } elseif (bcsub($account->count, $value, 0) > 0) {
                        $value = bcsub($account->count, $value, 0);
                        $account->count = bcsub($account->count, $value);
                        $logType = 0;
                    } else {
                        $value = bcsub($account->count, $value, 0) * -1;
                        $account->count = bcadd($account->count, $value);
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
            $log = SmsAccountLog::create([
                'uniacid' => $account->uniacid,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->count,
                "adminId" => $adminId,
                'behavior' =>  SmsAccountLog::BASE,
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            Log::info($e->getMessage());
            return false;
        }
    }
    /**
     * 系统赠送
     */
    public static function giving($uniacid, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = SmsAccount::where('uniacid', $uniacid)->first();
            if (empty($account)) {
                throw new BadRequestException('账户不存在');
            }
            $account->count =  bcadd($account->count, $value, 0);
            $logType = 1;
            $account->save();
            $account->refresh();
            $log = SmsAccountLog::create([
                'uniacid' => $account->uniacid,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->count,
                "adminId" => $adminId,
                'behavior' =>  SmsAccountLog::GIVING,
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            Log::info($e->getMessage());
            return false;
        }
    }

    /**
     * 充值
     */
    public static function topUp($uniacid, $value, $adminId = 0, $notes = '', $orderSn = '')
    {
        try {
            DB::beginTransaction();
            $account = SmsAccount::where('uniacid', $uniacid)->first();
            if (empty($account)) {
                throw new BadRequestException('账户不存在');
            }

            $account->count =  bcadd($account->count, $value, 0);
            $logType = 1;
            $account->save();
            $account->refresh();
            $log = SmsAccountLog::create([
                'uniacid' => $account->uniacid,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->count,
                "adminId" => $adminId,
                'behavior' =>  SmsAccountLog::TOPUP,
                'orderSn' => $orderSn
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
     * 套餐赠送
     */
    public static function setMate($uniacid, $value, $adminId = 0, $notes = '')
    {
        try {
            DB::beginTransaction();
            $account = SmsAccount::where('uniacid', $uniacid)->first();
            if (empty($account)) {
                throw new BadRequestException('账户不存在');
            }

            $account->count =  bcadd($account->count, $value, 0);
            $logType = 1;
            $account->save();
            $log = SmsAccountLog::create([
                'uniacid' => $account->uniacid,
                'type' => $logType,
                'value' => $value,
                'notes' => $notes,
                'atLast' => $account->count,
                "adminId" => $adminId,
                'behavior' =>  SmsAccountLog::TOPUP,
            ]);
            if ($log) {
                Db::commit();
                return true;
            }
            Db::rollBack();
            return false;
        } catch (\Exception $e) {
            Db::rollBack();
            Log::info($e->getMessage());
            return false;
        }
    }
}
