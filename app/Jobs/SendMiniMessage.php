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

class SendMiniMessage extends  Task
{
    protected $message;
    protected $uniacid;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($message)
    {
        $this->message = $message['message'];
        $this->uniacid = $message['uniacid'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('_______SendMiniMessageStart___________');
        Log::info($this->message);
        $app = ChannelOpenWechat::miniProgram($this->uniacid);
        $res = $app->subscribe_message->send($this->message);
        Log::info($res);
        Log::info('_______SendMiniMessageEnd___________');
    }

    public function finish()
    {
    }
}
