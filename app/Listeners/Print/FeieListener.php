<?php

namespace App\Listeners\PayGift;

use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Events\PayGiftEvent;
use App\Events\PrintEvent;
use App\Models\Coupon\MemberCoupon;
use App\Models\Member\Vip;
use App\Models\MemberAccountLog;
use App\Services\CouponService;
use App\Services\MemberAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\PayGift\Receive;
use App\Models\Printer;
use App\Models\PrinterLog;
use App\Services\Print\FeieContent;
use App\Services\Print\FeieLabelContent;

class FeieListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\MemberRegisteredEvent  $event
     * @return void
     */
    public function handle(PrintEvent $event)
    {
        $type = $event->type;
        $order = $event->order;
        $printers = collect($event->printer)->where('type', 1)->where('vendor', 'feie')->toArray();
        collect($printers)->each(function ($printer) use ($type, $order) {
            switch ($type) {
                case "takeout":
                    $contents = $this->takeoutContent($order, $printer['rule']);
                    break;
            }
            $printer_type = 1;
            $data = Printer::feiPrint($printer, $contents, 3);
            $respond = json_decode($data, true);
            if ($respond['msg'] == 'ok' && $respond['ret'] == 0) {
                $respond['msg'] = '成功';
            }
            PrinterLog::registerLog($printer, $order->orderSn, $printer_type, $contents, $data, $respond['msg'], $respond['data'], $v['rule']['config']['qtWmJoin'] ?? 1);
        });
    }

    /**
     * 外卖场景打印内容
     */
    public function takeoutContent($order, $rule)
    {
        $content = '';
        if (isset($rule['config']['qtWmBusiness']) && $rule['config']['qtWmBusiness'] > 0) {
            $num = $rule['config']['qtWmBusiness'] ?? 1;
            for ($i = 0; $i < $num; $i++) {
                $content .= FeieContent::storeContents($order);
            }
        }
        if (isset($rule['config']['qtWmCustomer']) && $rule['config']['qtWmCustomer'] > 0) {
            $num = $rule['config']['qtWmCustomer'] ?? 1;
            for ($i = 0; $i < $num; $i++) {
                $content .= "<BR><BR><BR><BR><BR>" .  FeieContent::userContents($order);
            }
        }
        if (isset($rule['config']['hcWmPrintNum']) && $rule['config']['hcWmPrintNum'] > 0) {
            $num = $rule['config']['hcWmPrintNum'] ?? 1;
            for ($i = 0; $i < $num; $i++) {
                $content .= "<BR><BR><BR><BR><BR>" .  FeieContent::oneContents($order, $rule);
            }
        }
        if (isset($rule['config']['zdPrintNum']) && $rule['config']['zdPrintNum'] > 0) {
            $num = $rule['config']['zdPrintNum'] ?? 1;
            for ($i = 0; $i < $num; $i++) {
                $content .= "<BR><BR><BR><BR><BR>" .  FeieContent::oneContents($order, $rule, 2);
            }
        }
    }
    /**
     * 储值订单打印内容
     */
    public function storeValueContent($order, $rule)
    {
        $str = '';
        if ($order->data['balanceSwitch'] == 1) {
            $str .= '赠送' . $order->data['balanceGive'] . '元余额,';
        }
        if ($order->data['integralSwitch'] == 1) {
            MemberAccountService::changeIntegral($order->userId, 1, $order->data['integralGive'], MemberAccountLog::INTEGRAL_BUY_GIVE, 0, '充值赠送积分', $orderIndex->orderSn);
            $str .= '赠送' . $order->data['integralGive'] . '积分,';
        }
        if ($order->data['levelSwitch'] == 1) {
            $vip = Vip::where('uniacid', $order->uniacid)->find($order->data['levelGive']);
            if ($vip && $vip->level > $order->user->vip->level) {
                $str .= '等级提升到' . $vip->name . '';
            }
        }
        if ($order->data['couponSwitch'] == 1) {
            $couponStr = '';
            foreach ($order->data['couponGive'] as $v) {
                $couponStr .= $v['name'] . '×' . $v['num'] . '、';
            }
            $str .= $couponStr;
        }
        $str = mb_substr($str, 0, -1, "UTF-8");
        if (isset($rule['config']['facepayNum']) && $rule['config']['facepayNum'] > 0) {
            
        }
    }
}
