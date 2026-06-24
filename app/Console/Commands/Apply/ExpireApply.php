<?php

namespace App\Console\Commands\Apply;

use App\Models\Admin\Apply;
use App\Models\Order\OrderIndex;
use App\Services\OrderService;
use App\Services\SmsService;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireApply extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expireApply';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '店铺过期';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        DB::table('apply')->where("timeType", 2)->where('endTime', "<=", date("Y-m-d H:i:s", time()))->update(['status' => 3]);
    }
}
