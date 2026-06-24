<?php

namespace App\Console\Commands\Table;

use App\Models\Admin\Apply;
use App\Models\StatisticsDay;
use App\Models\Store;
use App\Models\StoredValue;
use App\Models\Tables\Table;
use App\Services\InStoreOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Refresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '桌台超时清台';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $list = DB::table("table")->where('expiredTime', "<=", date("Y-m-d H:i:s", time()))->get();
        foreach($list as $key =>$v){
            InStoreOrderService::complete($v->orderSn);
        }
        DB::table("table")->where('expiredTime', "<=", date("Y-m-d H:i:s", time()))->update([
            'state' => 0,
            'people' => 0,
            'orderSn' => null,
            'expiredTime' => null,
            'scan' => 0,
            "openTime" => null
        ]);
    }
}
