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

class OrderTj extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:tj';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '订单统计';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $orderList = DB::table('order_index')->where('state', ">", 0)
            ->where('isSub', 0)->where('isTj', 0)
            ->orderBy('id', 'desc')
            ->limit(100)->get();
        collect($orderList)->each(function ($orderIndex) {
            try {
                StaticService::tongji($orderIndex->orderSn);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        });
    }
}
