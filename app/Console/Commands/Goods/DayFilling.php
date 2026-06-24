<?php

namespace App\Console\Commands\Goods;

use App\Models\Admin\Apply;
use App\Models\StatisticsDay;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DayFilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:dayFilling';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '门店商品置满';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        DB::table('store_goods_sku')->where('dayFilling', 1)->update([
            'surplusInventory' => DB::raw('`inventory`')
        ]);
    }
}
