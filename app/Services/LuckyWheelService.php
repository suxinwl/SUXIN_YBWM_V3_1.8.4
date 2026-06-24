<?php

namespace App\Services;

use App\Models\LuckyWheel;
use App\Models\MemberAccount;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class LuckyWheelService extends BaseService
{
    public static function check($orderIndex)
    {
        try {
            DB::beginTransaction();
            if ($orderIndex->type != 1 && $orderIndex->type != 4) {
                DB::commit();
                return "非外卖/堂食订单";
            }

            $luckyConfig = LuckyWheel::where('uniacid', $orderIndex->uniacid)->first();
            if (!$luckyConfig || $luckyConfig->state != 1) {
                DB::commit();
                return "抽奖未开启";
            }

            //不包含门店
            if ($luckyConfig->storeType == 2 && !in_array($orderIndex->storeId, $luckyConfig->storeIds)) {
                DB::commit();
                return "门店未参与抽奖";
            }

            //就餐方式
            // acquireMethods:
            // 0: "自取",
            // 1: "配送",
            // 2: "堂食",
            // 3: "快递",
            // 4: "外卖",
            if ($orderIndex->type == 1 ) {
                // 自取 配送
                // diningType：
                // 0 =>"外送",
                // 1 => "打包带走",
                // 2 => "店内就餐"
                if(!in_array($orderIndex->subOrder->diningType, [0,1,2,30])){
                    DB::commit();
                    return "未知就餐方式";
                }
                if ($orderIndex->subOrder->diningType == 0 && !in_array(4, $luckyConfig->acquireMethods)) {
                    DB::commit();
                    return "不支持外送";
                }
                if ($orderIndex->subOrder->diningType == 1 && !in_array(0, $luckyConfig->acquireMethods)) {
                    DB::commit();
                    return "不支打包带走";
                }
                if ($orderIndex->subOrder->diningType == 2 && !in_array(1, $luckyConfig->acquireMethods)) {
                    DB::commit();
                    return "不支店内就餐";
                }
                if ($orderIndex->subOrder->diningType == 30 && !in_array(3, $luckyConfig->acquireMethods)) {
                    DB::commit();
                    return "不支持快递";
                }

            }

            if ($orderIndex->type == 4 && !in_array(2, $luckyConfig->acquireMethods)) {
                DB::commit();
                return "不支持堂食";
            }

            //门槛
            if (bccomp($luckyConfig->threshold, '0.00', 2) !== 0 && bccomp($orderIndex->subOrder->money, $luckyConfig->threshold, 2) < 0) {
                DB::commit();
                return "订单金额不满足门槛";
            }


            $account = MemberAccount::where('userId', $orderIndex->userId)->first();
            if (empty($account)) {
                DB::commit();
                return "用户不存在";
            }
            //判断获取方式
            if ($account->lastLuckyTime) {
                $givenDate = new DateTime($account->lastLuckyTime);
                $now = new DateTime();
                $interval = $now->diff($givenDate);
                // 0: "下单赠送抽奖次数",
                // 1: "仅第一次下单赠送抽奖次数",
                // 2: "每天下单赠送抽奖次数",
                // 3: "每周下单赠送抽奖次数",
                // 4: "每月下单赠送抽奖次数"
                switch ($luckyConfig->acquireType) {
                    case 1:
                        DB::commit();
                        return "用户非第一次下单";
                    case 2:
                        if ($interval->days < 1) {
                            DB::commit();
                            return "一天内已经获得抽奖次数了";
                        }
                        break;
                    case 3:
                        if ($interval->days < 7) {
                            DB::commit();
                            return "一周内已经获得抽奖次数了";
                        }
                        break;
                    case 4:
                        if ($interval->days < 30) {
                            DB::commit();
                            return "一个月内已经获得抽奖次数了";
                        }
                        break;
                    default:
                        break;
                }
            }

            //条件通过，添加次数
            $account->luckyAttempts = bcadd($account->luckyAttempts, $luckyConfig->count);
            $account->lastLuckyTime = date('Y-m-d H:i:s');
            $account->save();

            //标志订单获取到抽奖次数
            $orderIndex->lucky = 1;
            $orderIndex->save();
            DB::commit();
            // todo：记录member_account日志
            return "新增抽奖次数成功";
        } catch (\Exception $e) {
            DB::rollBack();
            return "新增失败" . $e->getMessage();
        }
    }
}
