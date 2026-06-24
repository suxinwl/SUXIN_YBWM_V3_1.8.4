<?php

namespace App\Jobs;

use App\Models\MemberAccountLog;
use App\Services\CouponService;
use App\Services\MemberAccountService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mail;

class PointsGoodsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $order;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $goods = $this->order->goods;
        DB::table('points_mall')->where('id', $goods['id'])->increment('sales', 1);
        DB::table('points_mall')->where('id', $goods['id'])->decrement('stock', 1);
        if ($goods['product_type'] == 1) {
            return true;
        }
        if ($goods['product_type'] == 2 && !empty($goods['coupon_collection'])) {
            CouponService::issue($goods['coupon_collection'], $this->order->userId, 13, ['source' => "pointsMallOrder:" . $this->order->id]);
        }
        if ($goods['product_type'] == 3 && $goods['balance'] > 0) {
            MemberAccountService::GiveChange($this->order->userId, 0, $goods['balance'], MemberAccountLog::BALANCE_POINTS, 0, "积分商城兑换{$goods['balance']}余额");
        }
        $this->order->state = 6;
        $this->order->completionTime = Carbon::now()->toDateTimeString();
        $this->order->save();
        $this->order->orderIndex->state = 6;
        $this->order->orderIndex->save();

        return true;
    }
}
