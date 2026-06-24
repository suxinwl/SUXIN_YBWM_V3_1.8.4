<?php

namespace App\Console\Commands\Apply;

use App\Models\Admin\Apply;
use App\Models\Order\OrderIndex;
use App\Services\OrderService;
use App\Services\SmsService;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Expire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apply:Expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '给过期的店铺发送消息提醒';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $startTime = date("Y-m-d 00:00:00", time());
        $endTime = date("Y-m-d 23:59:59", time());
        $list = Apply::where("timeType", 2)->where('endTime', ">=", $startTime)->where('endTime', "<=", $endTime)->get();
        if (!empty($list)) {
            $sms =  new SmsService();
            $code = randomAESKey();
            foreach ($list as $key => $v) {
                if ($v->admin->mobile) {
                    try {
                        $sms->applyExpSms($v->admin->mobile, ['shopName' => $v->name]);
                    } catch (\Exception $e) {
                        var_dump($e->getMessage());
                    }
                }
            }
        }
    }
}
