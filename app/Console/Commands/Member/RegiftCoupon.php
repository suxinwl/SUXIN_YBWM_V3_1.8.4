<?php

namespace App\Console\Commands\Member;

use App\Models\Coupon\MemberCoupon;
use App\Models\Coupon\Regift;
use App\Models\Delivery\Order;
use App\Models\Member;
use App\Models\Order\OrderIndex;
use App\Models\TakeOut\Delivery;
use App\Services\DeliveryService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RegiftCoupon extends Command
{
    protected $signature = 'member:regiftCouponOverdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '超24小时未转赠成功的优惠券退回';

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
        Regift::where('expiredTime', '<', date("Y-m-d H:i:s", time()))->where('state', 0)->update([
            'state'=>2
        ]);
    }
}
