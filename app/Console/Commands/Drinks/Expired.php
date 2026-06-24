<?php

namespace App\Console\Commands\Drinks;

use App\Models\Admin\Apply;
use App\Models\Drinks\Log as DrinksLog;
use App\Models\Drinks\Order;
use App\Models\StatisticsDay;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Expired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drinks:expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '存酒过期';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $start = date("Y-m-d H:i:59", time());
        $list = Order::where("expiredTime", '<=', $start)->where('state', 1)->limit(100)->get();
        foreach ($list as $key => $order) {
            $model = new DrinksLog();
            $model->uniacid = $order->uniacid;
            $model->storeId = $order->storeId;
            $model->userId = $order->userId;
            $model->drinksId = $order->drinksId;
            $model->drinksOrderId = $order->id;
            $model->num = $order->residue;
            $model->type = 3;
            $model->score = 0;
            $model->adminId = 0;
            $model->save();
            $order->state = 3;
            $order->residue = 0;
            $order->save();
        }
    }
}
