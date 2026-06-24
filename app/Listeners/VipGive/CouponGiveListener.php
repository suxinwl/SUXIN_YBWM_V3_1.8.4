<?php

namespace App\Listeners\VipGive;

use App\Events\MemberRegisteredEvent;
use App\Models\MemberAccountLog;
use App\Services\CouponService;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
 
class CouponGiveListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\MemberRegisteredEvent  $event
     * @return void
     */
    public function handle(MemberRegisteredEvent $event)
    {
        $vip = $event->member->vip;
        if (empty($vip) || $vip->couponSwitch == 0 || empty($vip->couponGive)) {
            return true;
        }
        CouponService::issue($vip->couponGive,$event->member->id,10);
    }
}
