<?php

namespace App\Console\Commands\Order;

use App\Models\Admin\Apply;
use App\Models\Order\OrderIndex;
use App\Models\StatisticsDay;
use App\Models\Store;
use App\Models\StoredValue;
use App\Models\Tables\Table;
use App\Services\BillService;
use App\Services\InStoreOrderService;
use App\Services\StaticService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Goods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:goods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '商品统计修复';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        DB::update('UPDATE `ybwm_v3_order_goods` LEFT JOIN `ybwm_v3_takeout_order` ON `ybwm_v3_takeout_order`.`orderSn`=`ybwm_v3_order_goods`.`orderSn` SET `ybwm_v3_order_goods`.`completionTime`=`ybwm_v3_takeout_order`.`completionTime`,`ybwm_v3_order_goods`.`state`=`ybwm_v3_takeout_order`.`state` where `ybwm_v3_order_goods`.`diningType` IN (0,1,2);');
        DB::update('UPDATE `ybwm_v3_order_goods` LEFT JOIN `ybwm_v3_instore_order` ON `ybwm_v3_instore_order`.`orderSn`=`ybwm_v3_order_goods`.`orderSn` SET `ybwm_v3_order_goods`.`completionTime`=`ybwm_v3_instore_order`.`completionTime`,`ybwm_v3_order_goods`.`state`=`ybwm_v3_instore_order`.`state` where `ybwm_v3_order_goods`.`state` !=  8  AND `ybwm_v3_order_goods`.`diningType` IN (4,5,6);');
    }
}
