<?php

namespace App\Console\Commands\Apply;

use App\Models\Admin\Apply;
use App\Models\StatisticsDay;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsValue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apply:statisticsValue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算每日统计余额支付';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $list = StatisticsDay::where('storeId', 0)->get();
        foreach ($list as $key => $v) {
            $v->balanceMoney = $v->getBalanceMoney();
            $v->balanceOrder = $v->getBalanceCount();
            $v->save();
        }
    }
}
