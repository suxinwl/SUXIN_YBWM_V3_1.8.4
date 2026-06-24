<?php

namespace App\Console\Commands\Goods;

use App\Models\Admin\Apply;
use App\Models\Order\OrderGoods;
use App\Models\StatisticsDay;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Sales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goods:sales';

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
        $models = DB::table('order_goods')->select(['spuId', 'uniacid', 'storeId'])
            ->addSelect([
                DB::raw("IFNULL(sum(num),0) as num"),
            ])
            ->whereIn('state', [6, 10])
            ->groupBy('spuId')
            ->get();
        collect($models)->each(function ($goods) {
            DB::table('goods_spu')->where('id', $goods->spuId)->update([
                'sales' => $goods->num
            ]);
        });
    }
}
