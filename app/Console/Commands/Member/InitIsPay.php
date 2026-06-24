<?php

namespace App\Console\Commands\Member;

use App\Models\Delivery\Order;
use App\Models\Member;
use App\Models\Order\OrderIndex;
use App\Models\TakeOut\Delivery;
use App\Services\DeliveryService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InitIsPay extends Command
{
    protected $signature = 'member:InitIsPay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除用户今日是否支付状态';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Member::withTrashed()->update(["isPay" => 0]);
    }
}
