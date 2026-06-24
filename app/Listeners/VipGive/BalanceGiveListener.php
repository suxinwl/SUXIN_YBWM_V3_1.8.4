<?php

namespace App\Listeners\VipGive;

use App\Events\MemberRegisteredEvent;
use App\Models\MemberAccountLog;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BalanceGiveListener implements ShouldQueue
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
        if (empty($vip) || empty($vip->balanceSwitch) || $vip->balance <= 0) {
            return true;
        }
        MemberAccountService::GiveChange($event->member->id, 0, $vip->balance, MemberAccountLog::BALANCE_VIP_GIVE, 0, "vip等级到达{$vip->name}赠送{$vip->balance}余额");
    }
}
