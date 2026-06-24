<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\Member\Job;
use App\Models\MemberAccountLog;
use App\Services\CouponService;
use App\Services\MemberAccountService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Mail;

class MemberJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $userId;
    public $memberJob;
    public $index;
    public $changeType;
    public $value;
    public function __construct($userId, $jobId, $index, $changeType = 0, $value = 0)
    {
        $this->userId = $userId;
        $this->memberJob = Job::find($jobId);
        $this->index = $index;
        $this->changeType = $changeType;
        $this->value = $value;
        Log::error("-----------MemberJob--------------------");
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if ($this->memberJob->type == 1) {
                //$type = 'integral';
                $res = MemberAccountService::changeIntegral($this->userId, $this->changeType, $this->value, MemberAccountLog::BASE, 0, '系统批量调整');
            }
            if ($this->memberJob->type == 2) {
                // $type = 'balance';
                MemberAccountService::changeBalance($this->userId,  $this->changeType, $this->value, MemberAccountLog::BASE, 0, '系统批量调整');
            }
            if ($this->memberJob->type == 3) {
                MemberAccountService::changeExp($this->userId,  $this->changeType, $this->value, MemberAccountLog::BASE, 0, '系统批量调整');
            }
            if ($this->memberJob->type == 4) {
                CouponService::issue($this->value,$this->userId,3);
            }
            $this->memberJob->success = $this->memberJob->success + 1;
            if ($this->index == $this->memberJob->memberCount) {
                $this->memberJob->state = 2;
            }
            $this->memberJob->saveQuietly();
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '-' . $e->getLine() . $e->getFile());
        }
    }
}
