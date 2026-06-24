<?php

namespace App\Console\Commands\Member;

use App\Models\Coupon\MemberCoupon;
use App\Models\Delivery\Order;
use App\Models\ExchangeCode\ExchangeCodeReceive;
use App\Models\Member;
use App\Models\Order\OrderIndex;
use App\Models\TakeOut\Delivery;
use App\Services\DeliveryService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Expired extends Command
{
    protected $signature = 'exchangeCode:expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '过期兑换券';

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
        ExchangeCodeReceive::whereHas('exchangeCode', function ($q) {
            return $q->where('startTime', '<', date("Y-m-d H:i:s"));
        })->where('state', 1)->update(['state', 3]);
    }
}
