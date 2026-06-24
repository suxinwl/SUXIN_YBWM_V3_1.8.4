<?php

namespace App\Console\Commands\Apply;

use App\Models\Admin\Apply;
use App\Models\StatisticsDay;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Statistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apply:statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每日统计数据初始化';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $key = "StatisticsDay:apply:" . date("m-d", time());
        if (!Cache::has($key)) {
            $list  = Apply::withTrashed()->get();
            foreach ($list as $key => $v) {
                $model = StatisticsDay::where("uniacid", $v->id)->where('storeId', 0)->where('day', date("Y-m-d", time()))->first();
                if (empty($model)) {
                    $yestoday =  StatisticsDay::select([
                        DB::raw("IFNULL(sum(storedValue) - sum(balanceMoney) - sum(sysSubStoredValue),0) as balance"),
                    ])
                        ->where('uniacid', $v->id)
                        ->where("storeId", 0)
                        ->first();
                    $data[] = ['uniacid' => $v->id, 'day' => date("Y-m-d", time()), 'startBalance' => $yestoday->balance ?? 0];
                }
            }
            if (!empty($data)) {
                StatisticsDay::insert($data);
            }
            Cache::set($key, 3600 * 24);
        }
    }
}
