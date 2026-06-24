<?php

namespace App\Jobs;

use App\Models\MiniVersion;
use App\Models\OpenWechatAuth;
use App\Models\Room;
use App\Models\RoomLog;
use App\Services\ChannelConfigService;
use App\Services\ConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Tasks\CloseRoomTask;
use Swoole\Coroutine;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Facades\Redis;

class MiniAuditJob extends  CronJob
{

    protected $i = 1;
    // !!! 定时任务的`interval`和`isImmediate`有两种配置方式（二选一）：一是重载对应的方法，二是注册定时任务时传入参数。
    // --- 重载对应的方法来返回配置：开始
    public function interval()
    {
        return 1000 * 60 * 15; // 每1秒运行一次
    }

    public function isImmediate()
    {
        return true; // 是否立即执行第一次，false则等待间隔时间后执行第一次
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function run()
    {
        $list = MiniVersion::where('state', 2)->get();
        foreach ($list as $key => $v) {
            echo $v->appid . "\n";
            $mini = OpenWechatAuth::where('authorizer_appid', $v->appid)->first();
            $res = ChannelOpenWechat::getAuditStatus($mini->uniacid, $v->auditid);
            echo json_encode($res, 320) . "\n";
            if ($res['errcode'] == 0) {
                if ($res['status'] == 1) {
                    $v->state = 1;
                    $v->reason = $res['reason'];
                    $v->screenshot = "";
                    $v->save();
                }

                if ($res['status'] == 4) {
                    $v->state = 4;
                    $v->reason = $res['reason'];
                    $v->screenshot = "";
                    $v->save();
                }
                if ($res['status'] == 0) {
                    $v->state = 0;
                    $v->audit_ok_time = date("Y-m-d H:i:s", time());
                    $res = ChannelOpenWechat::release($mini->uniacid);
                    if ($res['code'] == 0) {
                        $v->state = 9;
                        $v->release_time = date("Y-m-d H:i:s", time());
                    }
                    $v->save();
                }
            }
        }
    }
}
