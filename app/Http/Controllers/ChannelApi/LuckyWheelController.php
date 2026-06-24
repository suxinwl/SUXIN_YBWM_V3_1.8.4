<?php

namespace App\Http\Controllers\ChannelApi;

use App\Models\LuckyWheelLog;
use App\Models\LuckyWheelReward;
use App\Models\LuckyWheel;
use App\Models\Member\Job;
use App\Models\MemberAccount;
use Illuminate\Support\Facades\DB;

class LuckyWheelController extends ApiController
{
    //中奖记录
    public function index()
    {
        $list = LuckyWheelLog::where('userId', $this->userId())->where("reward_name",'!=',"谢谢参与")->orderBy('created_at', 'desc')
            ->paginate($request->pageSize ?? 100, '*', 'pageNo');
        return $this->success($list);
    }

    public function awardsForUser()
    {
        $list = LuckyWheelReward::where('uniacid', $this->uniacid())
            ->orderBy('created_at', 'desc')
            ->get();
        return $this->success($list);
    }

    public function drawALottery()
    {
        try {
            DB::beginTransaction();
            $config = LuckyWheel::where('uniacid', $this->uniacid())->first();
            if (!$config || $config->state != 1) {
                return $this->failed('大转盘活动未开启');
            }
            $list = LuckyWheelReward::where('uniacid', $this->uniacid())
//                ->where('stock', '>', 0)
                ->get();
            if (empty(array($list))) {
                return $this->failed('没有设置奖品列表');
            }
            $account = MemberAccount::where('userId', $this->userId())->first();

            if ($account->luckyAttempts <= 0) {
                return $this->failed('抽奖次数不足');
            }
            // 计算总概率
            $totalProbability = $list->sum('probability');
            if ($totalProbability <= 0) {
                return $this->failed('奖品概率未正确设置');
            }

            // 生成一个0到总概率之间的随机数
            $randomNumber = mt_rand(0, $totalProbability * 100) / 100;

            // 根据随机数选择奖品
            $currentSum = 0;
            $selectedReward = null;

            foreach ($list as $reward) {
                $currentSum += $reward->probability;
                if ($randomNumber <= $currentSum) {
                    $selectedReward = $reward;
                    break;
                }
            }



            if (!$selectedReward) {
                return $this->failed('抽奖失败，请重试');
            }

            if($selectedReward->stock <= 0){
                $selectedReward->name = "奖品库存不足";
                return $this->success($selectedReward);
            }
            //发放，并次数 - 1
            $account->luckyAttempts = bcsub($account->luckyAttempts, 1);
            $account->save();
            //减少奖品库存
            if ($selectedReward->name != "谢谢参与") {
                $selectedReward->stock = bcsub($selectedReward->stock, 1);
                $selectedReward->save();
            }
            //发放优惠券
            if ($selectedReward->type == 2) {
                $job = new Job();
                $job->fill([
                    'type' => 4,
                    'jobType' => 4,
                    'data' => [$this->userId()],
                    'changeType' => 1,
                    'value' => [
                        ['id' => $selectedReward->couponId, 'num' => 1],
                    ]]);
                $job->uniacid = $this->uniacid();
                $job->storeId = $this->storeId();
                $job->save();
            }

            //发放记录
            LuckyWheelLog::create([
                'userId' => $this->userId(),
                'rewardId' => $selectedReward->id,
                'uniacid' => $this->uniacid(),
                'count' => 1,
                'reward_name' => $selectedReward->name,
                'rewardPic' => $selectedReward->pic,
                'state' => $selectedReward->type == 2 ? 2 : 0
            ]);
            DB::commit();
            return $this->success($selectedReward);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed("抽奖错误：" . $e->getMessage());
        }
    }

}
