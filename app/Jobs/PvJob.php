<?php

namespace App\Jobs;

use App\Models\StatisticsDay;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Mail;

class PvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uniacid;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uniacid)
    {
        $this->uniacid = $uniacid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        StatisticsDay::where('uniacid', $this->uniacid)
            ->where('day', date("Y-m-d", time()))
            ->increment('pv', 1);
    }
}
