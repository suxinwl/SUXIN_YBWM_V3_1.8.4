<?php

namespace App\Console\Commands\Order;

use App\Events\PartnerEvent;
use App\Models\Admin\Apply;
use App\Models\Order\OrderIndex;
use App\Models\StatisticsDay;
use App\Models\Store;
use App\Models\StoredValue;
use App\Models\Tables\Table;
use App\Services\BillService;
use App\Services\InStoreOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Bill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:bill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '订单出帐';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $orderList = OrderIndex::where('isSub', 0)->where('expiredTime', "<=", date("Y-m-d H:i:s", time()))
        ->where('state', 6)
        ->limit(10)
        ->get();
        collect($orderList)->each(function ($orderIndex) {
            try {
                $bill = BillService::initBill($orderIndex);
                if ($bill) {
                    $orderIndex->state = 10;
                    $orderIndex->expiredTime = null;
                    $orderIndex->save();
                    if ($orderIndex->type == 4) {
                        $ids = collect($orderIndex->suborder->suborder)->pluck('orderSn')->all();
                        if ($ids) {
                            OrderIndex::whereIn('orderSn', $ids)->where('state', 6)->update([
                                'state' => 10,
                                'expiredTime' => null
                            ]);
                        }
                    }
                    //event(new PartnerEvent($orderIndex));
                }
                return true;
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        });
    }
}
