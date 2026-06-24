<?php

namespace App\Console\Commands\EquityCard;

use App\Events\EquityCardEvent;
use App\Models\Coupon\MemberCoupon;
use App\Models\Delivery\Order;
use App\Models\EquityCard\Member as EquityCardMember;
use App\Models\ExchangeCode\ExchangeCodeReceive;
use App\Models\Member;
use App\Models\Order\OrderIndex;
use App\Models\TakeOut\Delivery;
use App\Services\DeliveryService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Coupon extends Command
{
    protected $signature = 'equityCard:coupon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '权益卡周期发券';

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
        $list = EquityCardMember::where('endTime', ">", Carbon::now()->toDateTimeString())
            ->where('nextTime', "<=", Carbon::now()->toDateTimeString())->limit(100)->get();
        foreach ($list as $key => $model) {
            event(new EquityCardEvent($model));
        }
    }
}
