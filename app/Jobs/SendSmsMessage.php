<?php

namespace App\Jobs;

use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class SendSmsMessage extends  Task
{
    protected $message;
    protected $mobile;
    protected $type;
    protected $uniacid;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->message = $data['mobile'];
        $this->uniacid = $data['uniacid'];
        $this->type = $data['type'];
        $this->message = $data['message'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('_______SendSmsMessageStart___________');
        $smsModel = new SmsService();
        $res = $smsModel->sendSms($this->mobile, $this->type, $this->message, $this->uniacid);
        Log::info($res);
        Log::info('_______SendSmsMessageEnd___________');
    }

    public function finish()
    {
    }
}
