<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\Member\Job;
use App\Models\MemberAccountLog;
use App\Models\Order\OrderIndex;
use App\Models\StatisticsDay;
use App\Models\StatisticsOrder;
use App\Services\MemberAccountService;
use App\Services\StaticService;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Mail;
use Illuminate\Support\Facades\Redis;
use App\Events\MemberRegisteredEvent;

class ImportMembersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $uniacid;
    public $domain;
    public $uid;

    public function __construct($domain, $uniacid, $uid)
    {
        $this->uniacid = $uniacid;
        $this->domain = $domain;
        $this->uid = $uid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $arr = ['uniacid' => $this->uniacid];
            $row = httpRequest($this->domain . '/channel/export/grab-members', $arr);
            if ($row['code'] == 200) {
                foreach ($row['data'] as $key => $v) {
                    $user = Member::where('uniacid', $this->uniacid)->where('mobile', $v['userTel'])->first();
                    if (!$user) {
                        $sex = $v['sex'] == '男' ? 1 : 0;
                        $model =  new Member();
                        $model->nickname = $v['userName'] ?: "用户_" . rand(10000, 99999);
                        $model->mobile = $v['userTel'] ?: '';
                        $model->uniacid = $this->uid;
                        $model->realname = $v['realName'] ?: null;
                        $model->sex = $sex;
                        $model->birthday = $v['birthday'] ?? null;
                        $model->labelId = null;
                        $model->groupId =  0;
                        $model->vipId = $model->initVip();
                        $model->vipCard = getVipCardNo();
                        $model->score = 9;
                        $model->vipCreateTime = date("Y-m-d H:i:s", time());
                        $model->save();
                        //$model->label()->sync($model->labelId);
                        Event(new MemberRegisteredEvent($model));
                        if (!empty($v['balance'])) {
                            MemberAccountService::changeBalance($model->id, 1, $v['balance'], MemberAccountLog::BASE, 0, '导入用户余额');
                        }
                        if (!empty($v['integral'])) {
                            MemberAccountService::changeIntegral($model->id, 1, $v['integral'], MemberAccountLog::BASE, 0, '导入用户积分');
                        }
                    }
                }
            } else {
                Redis::delete($this->domain . $this->uniacid);
            }
        } catch (\Exception $e) {
        }
    }
}
