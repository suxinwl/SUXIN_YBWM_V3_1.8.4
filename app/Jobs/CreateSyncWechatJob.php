<?php

namespace App\Jobs;

use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Cache;

class CreateSyncWechatJob extends  Task
{
    protected $uniacid;
    protected $start;
    protected $total = 0;
    protected $count = 0;
    protected $next_openid;

    /**
     * 创建同步微信用户同步任务
     *
     *
     * @return void
     */
    public function __construct($message)
    {
        $this->uniacid = $message['uniacid'];
        $this->start = $message['start'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('_______CreatSyncWechatJobStart___________');
        $app = ChannelOpenWechat::officialAccount($this->uniacid);
        $list =  $app->user->list($this->start);
        if (isset($list['errmsg'])) {
            echo $list;
            Cache::put('syncWechatUser:' . $this->uniacid, 0);
        } else {
            echo 'total:' . $list['total'] . "\n";
            echo 'count:' . $list['count'] . "\n";
            echo 'next_openid:' . $list['next_openid'] . "\n";
            $this->total = $list['total'];
            $this->count = $list['count'];
            $this->next_openid = $list['next_openid'];
            foreach ($list['data']['openid'] as $key => $user) {
                try {
                    $task = new SyncWechatUserJob(['uniacid' => $this->uniacid, 'openid' => $user]);
                    $ret = Task::deliver($task);
                    echo $ret;
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    Log::info($e->getMessage());
                }
            }
        }
        Log::info('_______CreatSyncWechatJobEnd___________');
    }

    public function finish()
    {
        if ($this->total > 10000 && $this->next_openid) {
            Task::deliver(new CreateSyncWechatJob(['uniacid' => $this->uniacid, 'start' => $this->next_openid]));
        } else {
            Cache::put('syncWechatUser:' . $this->uniacid, 0);
        }
    }
}
