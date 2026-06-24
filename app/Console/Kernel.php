<?php

namespace App\Console;

use App\Console\Commands\Apply\DelLog;
use App\Console\Commands\Apply\ExpireApply;
use App\Console\Commands\Apply\Statistics;
use App\Console\Commands\Order\OrderQuery;
use App\Console\Commands\Store\Statistics as StoreStatistics;
use App\Console\Commands\Apply\Submit;
use App\Console\Commands\Bill\QueryProfitsharing;
use App\Console\Commands\Drinks\Expired;
use App\Console\Commands\EquityCard\Coupon as EquityCardCoupon;
use App\Console\Commands\Goods\DayFilling;
use App\Console\Commands\Member\BirthdayPack;
use App\Console\Commands\Member\Coupon;
use App\Console\Commands\Member\InitIsPay;
use App\Console\Commands\Member\RegiftCoupon;
use App\Console\Commands\Order\Bill;
use App\Console\Commands\Order\CloseExpiredOrder;
use App\Console\Commands\Order\DeliveryCall;
use App\Console\Commands\Order\ExpiredInAppointmentOrder;
use App\Console\Commands\Order\ExpiredInstoreOrder;
use App\Console\Commands\Order\Goods;
use App\Console\Commands\Order\OrderTj;
use App\Console\Commands\Table\Refresh;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(Statistics::class)->withoutOverlapping()->dailyAt("00:00"); //10分钟一次
        $schedule->job(StoreStatistics::class)->withoutOverlapping()->dailyAt("00:00"); //10分钟一次
        $schedule->job(InitIsPay::class)->withoutOverlapping()->dailyAt("00:00"); //
        $schedule->job(ExpireApply::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(CloseExpiredOrder::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(DeliveryCall::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(Submit::class)->withoutOverlapping()->everyTenMinutes(); //10分钟一次
        $schedule->job(Coupon::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(RegiftCoupon::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(DelLog::class)->withoutOverlapping()->dailyAt("01:00"); //2小时一次
        $schedule->job(Refresh::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(Bill::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(QueryProfitsharing::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(ExpiredInAppointmentOrder::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(ExpiredInstoreOrder::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(OrderTj::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(DayFilling::class)->withoutOverlapping()->dailyAt("00:01"); //10分钟一次
        $schedule->job(BirthdayPack::class)->withoutOverlapping()->hourly(); //10分钟一次
        $schedule->job(Expired::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(Goods::class)->withoutOverlapping()->everyTenMinutes(); //10分钟一次
        $schedule->job(EquityCardCoupon::class)->withoutOverlapping()->everyMinute(); //置顶时间重置
        $schedule->job(OrderQuery::class)->withoutOverlapping()->everyFiveMinutes(); //置顶时间重置
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
