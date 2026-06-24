<?php

namespace App\Jobs;


use App\Models\Order\OrderIndex;
use App\Tasks\OrderBillJob;

use Hhxsv5\LaravelS\Swoole\Task\Task;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Facades\Log;

class MakeAccountJob extends  CronJob
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */


    /**
     * The console command description.
     *
     * @var string
     */


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function interval()
    {
        return 1000; // 每1秒运行一次
    }

    public function isImmediate()
    {
        return false; // 是否立即执行第一次，false则等待间隔时间后执行第一次
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function run()
    {
        $endTime = date("Y-m-d H:i:s", time());
    }
}
