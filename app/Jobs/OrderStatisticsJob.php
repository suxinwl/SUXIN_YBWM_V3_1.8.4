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
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Mail;

class OrderStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $orderSn;

    public function __construct($orderSn)
    {
        $this->orderSn = $orderSn;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        StaticService::tongji($this->orderSn);
    }
}
