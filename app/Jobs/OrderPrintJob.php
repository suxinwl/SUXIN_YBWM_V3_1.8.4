<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\Member\Job;
use App\Models\MemberAccountLog;
use App\Models\Order\OrderIndex;
use App\Models\StatisticsDay;
use App\Services\InStoreOrderService;
use App\Services\MemberAccountService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Mail;

class OrderPrintJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $orderId;
    public $type;
    public function __construct($orderId, $type)
    {
        $this->orderId = $orderId;
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
            InStoreOrderService::print($this->orderId, $this->type);
        } catch (\Exception $e) {
            Log::error($e->getMessage() . '-' . $e->getLine() . $e->getFile());
        }
    }
}
