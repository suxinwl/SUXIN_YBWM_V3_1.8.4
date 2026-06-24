<?php

namespace App\Console\Commands\Store;

use App\Models\Admin\Apply;
use App\Models\StatisticsDay;
use App\Models\Store;
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
    protected $signature = 'store:statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '门店每日统计数据初始化';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $key = "StatisticsDay:apply:" . date("m-d", time());
        if (!Cache::has($key)) {
            $list  = Store::select(["id", 'uniacid'])->withTrashed()->get();
            foreach ($list as $key => $v) {
                $model = StatisticsDay::where('storeId', $v->id)->where("uniacid", $v->uniacid)->where('day', date("Y-m-d", time()))->first();
                if (empty($model)) {
                    $data[] = ['storeId' => $v->id, 'uniacid' => $v->uniacid, 'day' => date("Y-m-d", time())];
                }
            }
            if (!empty($data)) {
                StatisticsDay::insert($data);
            }
            Cache::set($key, 3600 * 24);
        }
    }
}
