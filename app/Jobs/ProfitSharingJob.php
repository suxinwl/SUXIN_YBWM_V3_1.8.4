<?php

namespace App\Jobs;

use App\Models\Order\Bill;
use App\Models\Order\OrderIndex;
use App\Services\BillService;
use App\Services\OpenWechat\ChannelOpenWechat;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Mail;

class ProfitSharingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $bill;
    public $type;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id,$type ='profit_sharing')
    {
        $this->bill = Bill::where('profit_sharing',1)->find($id);
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if($this->bill){
                if($this->type =='profit_sharing'){
                    BillService::profit_sharing($this->bill);
                }
                if($this->type =='unfreeze'){
                    BillService::unfreeze($this->bill);
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
