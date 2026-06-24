<?php

namespace App\Console\Commands\Member;

use App\Models\Coupon\MemberCoupon;
use App\Models\Delivery\Order;
use App\Models\Member;
use App\Models\Order\OrderIndex;
use App\Models\TakeOut\Delivery;
use App\Services\DeliveryService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Coupon extends Command
{
    protected $signature = 'member:couponOverdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除用户过期未适用的红包';

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
        MemberCoupon::where('state', 1)->where('endTime', "<", date("Y-m-d H:i:s", time()))->update(['state' => 0]);
    }
}
