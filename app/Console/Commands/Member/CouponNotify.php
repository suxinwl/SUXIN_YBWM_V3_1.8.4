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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CouponNotify extends Command
{
    protected $signature = 'member:couponOverdueNotify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '会员红包到期提醒';

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
        $list = MemberCoupon::select('*')
            ->addSelect(DB::raw('IFNULL(sum(if(state = 1 and deleted_at is null,1,0)),0) as num'))
            ->where('state', 1)
            ->where('endTime','<', date("Y-m-d H:i:s", strtotime("+30 day")))
            ->groupBy('couponId', 'userId')
            ->orderBy('id', 'desc')
            ->get();
    }
}
