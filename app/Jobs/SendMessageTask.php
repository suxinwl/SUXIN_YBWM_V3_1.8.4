<?php

namespace App\Jobs;

use App\Services\MessageConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class SendMessageTask extends  Task
{
    protected $orderId;
    protected $type;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($message)
    {
        $this->orderId = $message['orderId'];
        $this->type = $message['type'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('_______SendMessageStart___________');
        MessageConfigService::sendMessage($this->orderId,$this->type);
        Log::info('_______SendMessageEnd___________');
    }

    public function finish()
    {

    }
}
