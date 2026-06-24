<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\MemberBind;
use App\Models\MemberSubscribe;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class SyncWechatUserJob extends  Task
{
    protected $uniacid;
    protected $openid;

    /**
     * 同步微信公众号用户
     *
     * @return void
     */
    public function __construct($message)
    {
        $this->uniacid = $message['uniacid'];
        $this->openid = $message['openid'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('_______SyncWechatUserJobStart___________');
        $app = ChannelOpenWechat::officialAccount($this->uniacid);
        $uniacid = $this->uniacid;
        echo $this->openid . "\n";
        $user = $app->user->get($this->openid);
        $wechatBind = MemberBind::where(function ($q) use ($user) {
            return $q->where('unionid', $user['unionid'])->where('type', 2);
        })->first();

        $miniBind = MemberBind::where(function ($q) use ($user) {
            return $q->where('unionid', $user['unionid'])->where('type', 1);
        })->first();
        if (empty($wechatBind) && !empty($miniBind)) {
            $memberBind = new MemberBind();
            $memberBind->userId = $miniBind->userId;
            $memberBind->type = 2;
            $memberBind->unionid = $user['unionid'];
            $memberBind->openid = $user['openid'];
            $memberBind->nickname = $miniBind->member->nickname;
            $memberBind->mobile = $miniBind->member->mobile ?? '';
            $memberBind->avatar = $miniBind->member->avatar ?? '';
            $memberBind->data = json_encode([], 320);
            $memberBind->save();
        }
        $memberSubscribe = MemberSubscribe::where('openid', $user['openid'])->first();
        if (empty($memberSubscribe)) {
            $memberSubscribe = new MemberSubscribe();
        }
        $memberSubscribe->openid = $user['openid'];
        $memberSubscribe->unionid = $user['unionid'] ?? '';
        $memberSubscribe->subscribe = $user['subscribe'];
        $memberSubscribe->save();
        Log::info('_______SyncWechatUserJobEnd___________');
    }

    public function finish()
    {
    }
}
