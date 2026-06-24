<?php

namespace App\Console\Commands\Bill;

use App\Console\Commands\Order\Bill;
use App\Jobs\ProfitSharingJob;
use App\Models\Admin\Apply;
use App\Models\Order\Bill as OrderBill;
use App\Models\StatisticsDay;
use App\Models\Store;
use App\Models\StoredValue;
use App\Models\Tables\Table;
use App\Services\BillService;
use App\Services\InStoreOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueryProfitsharing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bill:QueryProfitsharing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '分账结果查询';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $list = OrderBill::whereIn("sharingState", [0,2,3])->where('profit_sharing', 1)->limit(10)->get();
        foreach ($list as $key => $v) {
            if ($v->sharingState == 0 || $v->sharingState == 2) {
                //BillService::profit_sharing($v); 
                dispatch(new ProfitSharingJob($v->id));
            }
            if ($v->sharingState == 3) {
                BillService::profit_query($v);
            }
        }
    }
}
