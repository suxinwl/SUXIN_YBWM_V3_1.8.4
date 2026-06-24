<?php

namespace App\Services;

use App\Enums\PayEnum;
use App\Events\EquityCardEvent;
use App\Events\OrderMessageEvent;
use App\Events\PartnerEvent;
use App\Events\PartyBEvent;
use App\Events\PayGiftEvent;
use App\Events\StoreMessageEvent;
use App\Jobs\OrderStatisticsJob;
use App\Jobs\PointsGoodsJob;
use App\Models\Coupon\MemberCoupon;
use App\Models\InStore\Order\Order;
use App\Models\Member;
use App\Models\MemberAccount;
use App\Models\Partner;
use App\Models\PrinterLog;
use App\Services\Print\DaquContent;
use App\Services\Print\JiaboContent;
use App\Services\Print\SpyunContent;
use App\Services\Print\XinyeContent;
use App\Services\Print\YlyContent;
use App\Traits\ResourceTrait;
use App\Models\MemberAccountLog;
use App\Models\OrderLog;
use App\Models\ShopAccountLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Post;
use App\Models\PostOrder;
use App\Models\Robot;
use App\Models\TopLevel;
use App\Models\Log as postLog;
use App\Models\Member\Vip;
use App\Models\OldWithNew\PartyA;
use App\Models\OldWithNew\PartyB;
use App\Models\Order\Discount;
use App\Models\Order\OrderGoods;
use App\Models\Order\OrderIndex;
use App\Models\Order\OrderPay;
use App\Models\Order\payLog;
use App\Models\Order\User;
use App\Models\StatisticsDay;
use App\Models\Store\AccountLog;
use App\Services\PrinterService;
use Illuminate\Support\Facades\Event;
use App\Models\Printer;
use App\Models\RefundOrder;
use App\Services\Print\FeieContent;
use App\Models\StoredValueOrder;
use App\Models\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Payload;
use App\Models\EquityCard\Member as equityCardMember;
use App\Services\OrderService;
use App\Services\InStoreOrderService;
use App\Services\StaticService;
class OrderNotifyService
{

    public static function takeout($message, $orderSn, $payTempId = 1)
    {
        try {
            $orderIndex = OrderIndex::where('type', 1)->unpaid()->where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                return false;
            }
            $orderIndex->thirdNo = $message['transaction_id'] ?? '';
            $orderIndex->payType = $message['trade_type'] ?? '';
            $orderIndex->payTime =  date("Y-m-d H:i:s", time());
            $orderIndex->profit_sharing = $message['profit_sharing'] ?? 0;
            $orderIndex->payer = $message['payer']['openid'] ?? $message['payer']['sub_openid'];
            $orderIndex->payChannel = $message['payChannel'];
            $orderIndex->payTempId = $payTempId;
            $orderIndex->state = 2;
            $orderIndex->save();
            OrderPay::updateOrCreate(['orderSn' => $orderIndex->orderSn], [
                'orderSn' => $orderIndex->orderSn,
                'prentOrderSn' => $orderIndex->orderSn,
                'thirdNo' => $orderIndex->thirdNo,
                'payType' => $orderIndex->payType,
                'payTempId' => $orderIndex->payTempId,
                'profit_sharing' => $orderIndex->profit_sharing,
                'payChannel' => $orderIndex->payChannel,
                'money' => $orderIndex->order->money,
                'state' => 6
            ]);
            $orderIndex->order->payTime = date("Y-m-d H:i:s", time());
            $orderIndex->order->pickNo = $orderIndex->order->getPickNo();
            $orderIndex->order->pickFix = $orderIndex->order->getPickFix();
            $orderIndex->order->state = 2;

            $orderIndex->order->save();
            $orderIndex->user->save();
            $orderIndex->order->setLog('用户已支付,等待门店接单');
            if ($orderIndex->payChannel == 1) {
                StoreAccountService::freezeAmount($orderIndex->storeId, 1, $orderIndex->order->money, AccountLog::FREEZE_ORDER_PAY, $orderIndex->userId, $orderIndex->orderSn);
            }
            Event(new OrderMessageEvent($orderIndex->order, 'pay'));
            Event(new OrderMessageEvent($orderIndex->order, 'newOrder'));
            //Event(new PartnerEvent($orderIndex->order));
            if ($orderIndex->order->autoReceive == 1) {
                OrderService::received($orderIndex->order->id, '门店自动接单，商品制作中');
            } else {
                Event(new StoreMessageEvent($orderIndex->order, 'newOrder'));
            }
            return true;
        } catch (\Exception $e) {
            file_put_contents('jspay.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }

    public static function storeValue($message, $orderSn, $payTempId = 1)
    {
        try {
            $orderIndex = OrderIndex::where('type', 2)->unpaid()->where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                return true;
            }
            //$shopName = $orderIndex->store->name;
            $orderIndex->thirdNo = $message['transaction_id'] ?? '';
            $orderIndex->profit_sharing = $message['profit_sharing'] ?? 0;
            $orderIndex->payType = $message['trade_type'] ?? '';
            $orderIndex->payTime =  date("Y-m-d H:i:s", time());
            $orderIndex->payer = $message['payer']['openid'] ?? $message['payer']['sub_openid'];
            $orderIndex->payChannel = $message['payChannel'];
            $orderIndex->payTempId = $payTempId;
            $orderIndex->state = 6;
            $orderIndex->expiredTime = date("Y-m-d H:i:s", time() + 3600);
            $orderIndex->save();
            $orderIndex->subOrder->payTime =  date("Y-m-d H:i:s", time());
            $orderIndex->subOrder->state = 2;
            $orderIndex->subOrder->save();
            $orderIndex->user->isPay++;
            $orderIndex->user->save();
            $orderIndex->subOrder->addUserPayStore();
            OrderPay::updateOrCreate(['orderSn' => $orderIndex->orderSn], [
                'orderSn' => $orderIndex->orderSn,
                'prentOrderSn' => '',
                'thirdNo' => $orderIndex->thirdNo,
                'payType' => $orderIndex->payType,
                'payTempId' => $orderIndex->payTempId,
                'profit_sharing' => $orderIndex->profit_sharing,
                'payChannel' => $orderIndex->payChannel,
                'money' => $orderIndex->subOrder->money,
                'state' => 6
            ]);
            //储值成为分销商

            $distributorConfig = ConfigService::getChannelConfig('distributor', $orderIndex->subOrder->uniacid,0);
            if ($distributorConfig['storedModel']&&$distributorConfig['storedValueMoney']) {
                $model = Partner::where('uniacid', $orderIndex->subOrder->uniacid)
                    ->where('userId', $orderIndex->subOrder->userId)
                    ->first();
                if(empty($model)){
                    $memberInfo = Member::where('uniacid', $orderIndex->subOrder->uniacid)->where('id', $orderIndex->subOrder->userId)->first();
                    if($distributorConfig['storedModel']==1&&$orderIndex->subOrder->money>=$distributorConfig['storedValueMoney']){
                        $model = new Partner();
                        $model->userId = $orderIndex->subOrder->userId;
                        $model->uniacid = $orderIndex->subOrder->uniacid;
                        $model->parentId = $memberInfo->partnerId;
                        if ($distributorConfig['authState'] == 1) {
                            $model->state = 1;
                        }
                        if ($distributorConfig['authState'] == 2) {
                            $model->state = 0;
                        }
                        $model->save();
                    }
                    $allMoney=StoredValueOrder::where('uniacid', $orderIndex->subOrder->uniacid)->where('id', $orderIndex->subOrder->userId)
                        ->where('state',2)->sum('money');
                    if($distributorConfig['storedModel']==2&&$orderIndex->subOrder->money<=$allMoney){
                        $model = new Partner();
                        $model->userId = $orderIndex->subOrder->userId;
                        $model->uniacid = $orderIndex->subOrder->uniacid;
                        $model->parentId = $memberInfo->partnerId;
                        if ($distributorConfig['authState'] == 1) {
                            $model->state = 1;
                        }
                        if ($distributorConfig['authState'] == 2) {
                            $model->state = 0;
                        }
                        $model->save();
                    }
                }

            }
            $user=Member::where('id',$orderIndex->subOrder->userId)->where('uniacid',$orderIndex->uniacid)->first();
            MemberAccountService::buyBalance($orderIndex->subOrder->userId, 1, $orderIndex->subOrder->money, MemberAccountLog::BALANCE_BUY, 0, '余额充值', $orderIndex->orderSn);
            $str = '';
            if ($orderIndex->subOrder->data['balanceSwitch'] == 1) {
                MemberAccountService::GiveChange($orderIndex->subOrder->userId, 1, $orderIndex->subOrder->data['balanceGive'], MemberAccountLog::BALANCE_GIVE, 0, '余额赠送', $orderIndex->orderSn);
                $str .= '赠送' . $orderIndex->subOrder->data['balanceGive'] . '元余额,';
            }
            if ($orderIndex->subOrder->data['integralSwitch'] == 1) {
                MemberAccountService::changeIntegral($orderIndex->subOrder->userId, 1, $orderIndex->subOrder->data['integralGive'], MemberAccountLog::INTEGRAL_BUY_GIVE, 0, '充值赠送积分', $orderIndex->orderSn);
                $str .= '赠送' . $orderIndex->subOrder->data['integralGive'] . '积分,';
            }
            if ($orderIndex->subOrder->data['expSwitch'] == 1) {
                MemberAccountService::changeExp($orderIndex->subOrder->userId, 1, $orderIndex->subOrder->data['expGive'], MemberAccountLog::EXP_BUY, 0, '充值赠送成长值', $orderIndex->orderSn);
                $str .= '赠送' . $orderIndex->subOrder->data['integralGive'] . '成长值,';
            }
            if ($orderIndex->subOrder->data['levelSwitch'] == 1) {
                if($orderIndex->subOrder->data['levelGive']>$user['vipId']){
                    $user->vipId=$orderIndex->subOrder->data['levelGive'];
                    $user->save();
                }
            }


            $growthSetting=ConfigService::getChannelConfig('growthSetting', $orderIndex->subOrder->uniacid);
            if ($growthSetting['growthState'] == 1) {
//                if ($growthSetting['giveType'] == 1) {
//                    $money  = round($orderIndex->subOrder->money);
//                    $vlaue = abs($money *  $growthSetting['oneYuanGive']);
//                    MemberAccountService::changeExp($orderIndex->subOrder->userId, 1, $vlaue, MemberAccountLog::EXP_BUY, 0, '充值赠送', $orderIndex->orderSn);
//                }
                $memberAccount=MemberAccount::where('userId',$orderIndex->subOrder->userId)->where('uniacid',$orderIndex->uniacid)->first();
                $vip = Vip::where('uniacid', $orderIndex->uniacid)->find($orderIndex->subOrder->data['levelGive']);
                if ($orderIndex->subOrder->data['levelSwitch'] == 1) {
                    if($vip->id>$user->vipId){
                        $str .= '等级提升到' . $vip->id . '级' . ',';
                        $user->vipId=$vip->id;
                        $user->save();
                    }
                    $nextVip=Vip::where('uniacid', $orderIndex->uniacid)->where('exp','<=',$memberAccount->exp)->orderBy('exp','desc')->first();
                    if($user->exp>$nextVip->exp&&$user->vipId<$nextVip->id){
                        $str .= '等级提升到' . $nextVip->name. ',';
                        $user->vipId=$nextVip->id;
                        $user->save();
                    }
                }


            }
            if ($orderIndex->subOrder->data['couponSwitch'] == 1) {
                CouponService::issue($orderIndex->subOrder->data['couponGive'], $orderIndex->subOrder->userId, 9);
                $couponStr = '';
                foreach ($orderIndex->subOrder->data['couponGive'] as $v) {
                    $couponStr .= $v['name'] . '×' . $v['num'] . '、';
                }
                $str .= $couponStr;
            }
            $str = mb_substr($str, 0, -1, "UTF-8");
            $orderIndex->subOrder->setFirst($orderIndex->userId);
            //dispatch(new OrderStatisticsJob($orderIndex->orderSn));
            StaticService::tongji($orderIndex->orderSn);
            if ($orderIndex->subOrder->statisticsData) {
                StatisticsDay::where(function ($q) use ($orderIndex) {
                    return $q->where(function ($q) use ($orderIndex) {
                        return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", 0);
                    })->orWhere(function ($q) use ($orderIndex) {
                        return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", $orderIndex->storeId);
                    });
                })->where("day", Carbon::now()->toDateString())
                    ->where("payMember", ">=", 0)
                    ->where("repurchase", ">=", 0)
                    ->where("newPayUser", ">=", 0)
                    ->where("newPayUser", ">=", 0)
                    ->update($orderIndex->subOrder->statisticsData);
            }
            $member = MemberAccount::where('userId', $orderIndex->subOrder->userId)->first();
            $printer = Printer::getHardware($orderIndex->uniacid, $orderIndex->storeId, '', 1);
            if ($printer) {
                foreach ($printer as $v) {
                    $content = '';
                    $printer_type = 1;
                    if (isset($v['rule']['config']['czPrintNum']) && $v['rule']['config']['czPrintNum'] > 0) {
                        switch ($v['vendor']) {
                            case 'feie';
                                if ($v['config']['printer_size'] == 2) {
                                    $className = "App\Services\Print\FeieMaxContent";
                                } else {
                                    $className = "App\Services\Print\FeieContent";
                                }
                                $content .= $className::rechargeContents($orderIndex->subOrder, $member->balance, $str);
                                $num = $v['rule']['config']['czPrintNum'] ?? 1;
                                $data = Printer::feiPrint($v, $content, 2, $num);
                                break;
                            case 'esLink';
                                if ($v['config']['printer_size'] == 2) {
                                    $className = "App\Services\Print\YlyMaxContent";
                                } else {
                                    $className = "App\Services\Print\YlyContent";
                                }
                                $content .= $className::rechargeContents($orderIndex->subOrder, $member->balance, $str);
                                $num = $v['rule']['config']['czPrintNum'] ?? 1;
                                $data = Printer::ylyPrint($v, $content, 2, $num);
                                break;
                            case 'spyun';
                                $content .= SpyunContent::rechargeContents($orderIndex->subOrder, $member->balance, $str);
                                $num = $v['rule']['config']['czPrintNum'] ?? 1;
                                $data = Printer::spyPrint($v, $content, 2, $num);
                                break;
                            case 'jiabo';
                                $content .= JiaboContent::rechargeContents($orderIndex->subOrder, $member->balance, $str);
                                $num = $v['rule']['config']['czPrintNum'] ?? 1;
                                $data = Printer::jiaboPrint($v, $content, 2, $num);
                                break;
                            case 'daqu';
                                $content .= DaquContent::rechargeContents($orderIndex->subOrder, $member->balance, $str);
                                $num = $v['rule']['config']['czPrintNum'] ?? 1;
                                $data = Printer::daquPrint($v, $content, 2, $num);
                                break;
                            case 'xinye';
                                if ($v['config']['printer_size'] == 2) {
                                    $className = "App\Services\Print\XinyeMaxContent";
                                } else {
                                    $className = "App\Services\Print\XinyeContent";
                                }
                                $content .= $className::rechargeContents($orderIndex->subOrder, $member->balance, $str);
                                $num = $v['rule']['config']['czPrintNum'] ?? 1;
                                $data = Printer::xinyePrint($v, $content, 2, $num);
                                break;
                        }
                        PrinterLog::registerLog($v, $orderIndex->orderSn, $printer_type, $content, $data, 7, '充值订单');
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            file_put_contents('jspay.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }


    public static function personPay($message, $orderSn, $payTempId = 1)
    {
        try {
            $orderIndex = OrderIndex::where('type', 3)->unpaid()->where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                return false;
            }
            $key = "persionPayNum:{$orderIndex->uniacid}:{$orderIndex->storeId}:{$orderIndex->userId}";
            $orderIndex->thirdNo = $message['transaction_id'] ?? '';
            $orderIndex->payTime =  date("Y-m-d H:i:s", time());
            $orderIndex->profit_sharing = $message['profit_sharing'] ?? 0;
            $orderIndex->payType = $message['trade_type'] ?? '';
            $orderIndex->costomPayId = $message['costomPayId'] ?? 0;
            $orderIndex->payer = $message['payer']['openid'] ?? $message['payer']['sub_openid'];
            $orderIndex->payChannel = $message['payChannel'];
            $orderIndex->payTempId = $payTempId;
            $orderIndex->state = 6;
            $orderIndex->expiredTime = date("Y-m-d H:i:s", time() + 3600 * 24);
            $orderIndex->save();
            OrderPay::updateOrCreate(['orderSn' => $orderIndex->orderSn], [
                'orderSn' => $orderIndex->orderSn,
                'prentOrderSn' => '',
                'thirdNo' => $orderIndex->thirdNo,
                'payType' => $orderIndex->payType,
                'payTempId' => $orderIndex->payTempId,
                'profit_sharing' => $orderIndex->profit_sharing,
                'money' => $orderIndex->subOrder->money,
                'payChannel' => $orderIndex->payChannel,
                'state' => 6
            ]);
            $orderIndex->subOrder->payTime =  date("Y-m-d H:i:s", time());
            $orderIndex->subOrder->state = 6;
            $orderIndex->subOrder->pickNo = $orderIndex->subOrder->getPickNo();
            $orderIndex->subOrder->pickFix = $orderIndex->subOrder->getPickFix();
            if ($orderIndex->userId) {
                if (Cache::has($key)) {
                    $orderIndex->subOrder->payNum = Cache::get($key) + 1;
                } else {
                    $orderIndex->subOrder->payNum = 1;
                }
                $orderIndex->user->isPay++;
                $orderIndex->user->payTime = date("y-m-d H:i:s", time());
                $orderIndex->user->save();
            }
            $orderIndex->subOrder->save();
            $orderIndex->subOrder->refresh();
            $orderIndex->subOrder->addUserPayStore();
            //$orderIndex->subOrder->setFirst($orderIndex->userId);
            if ($orderIndex->payChannel == 1) {
                StoreAccountService::freezeAmount($orderIndex->storeId, 1, $orderIndex->subOrder->money, AccountLog::FREEZE_ORDER_PAY, $orderIndex->userId, $orderIndex->orderSn);
            }
            if ($orderIndex->subOrder->statisticsData) {
                StatisticsDay::where(function ($q) use ($orderIndex) {
                    return $q->where(function ($q) use ($orderIndex) {
                        return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", 0);
                    })->orWhere(function ($q) use ($orderIndex) {
                        return $q->where("uniacid", $orderIndex->uniacid)->where("storeId", $orderIndex->storeId);
                    });
                })->where("day", Carbon::now()->toDateString())
                    ->where("payMember", ">=", 0)
                    ->where("repurchase", ">=", 0)
                    ->where("newPayUser", ">=", 0)
                    ->where("newPayUser", ">=", 0)
                    ->update($orderIndex->subOrder->statisticsData);
            }
            if ($orderIndex->subOrder->integral > 0) {
                MemberAccountService::changeIntegral($orderIndex->subOrder->userId, 1, $orderIndex->subOrder->integral, MemberAccountLog::INTEGRAL_ORDER_GIVE, 0, "订单{$orderIndex->orderSn}赠送", $orderIndex->orderSn);
            }

            if ($orderIndex->subOrder->exp > 0) {
                MemberAccountService::changeExp($orderIndex->subOrder->userId, 1, $orderIndex->subOrder->exp, MemberAccountLog::EXP_ORDER_GIVE, 0, "订单{$orderIndex->orderSn}赠送", $orderIndex->orderSn);
            }

            /**
             * 支付有礼
             */
            if ($orderIndex->subOrder->payGiftId > 0) {
                Event(new PayGiftEvent($orderIndex->order, 'pay'));
            }
            if ($orderIndex->subOrder->user->payOrder == 0) {
                $partyB = PartyB::where('uniacid', $orderIndex->uniacid)
                    ->where('userId', $orderIndex->userId)
                    ->where('firstPayState', 0)
                    ->first();
                if ($partyB) {
                    $partyA = PartyA::where('uniacid', $orderIndex->uniacid)
                        ->where('userId', $partyB->partyAid)
                        ->where('oldWithNewId', $partyB->oldWithNewId)
                        ->first();
                    if ($partyA) {
                        Event(new PartyBEvent($partyB, $partyA, 'firstPay'));
                    }
                }
            }
            Event(new StoreMessageEvent($orderIndex, 'pay'));
            Cache::increment($key, 1);
            // dispatch(new OrderStatisticsJob($orderIndex->orderSn));
            StaticService::tongji($orderIndex->orderSn);
            // OrderService::otherPrintOrder(2, $orderIndex);
            InStoreOrderService::print($orderIndex->subOrder->id, 8);
            return true;
        } catch (\Exception $e) {
            file_put_contents('jspay.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }


    public static function inStore($message, $orderSn, $payTempId = 1, $userId = 0)
    {

        try {
            $orderIndex = OrderIndex::where('type', 4)->unpaid()->where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                return false;
            }
            $orderIndex->payChannel = $message['payChannel'];
            if ($orderIndex->payChannel == 1) {
                StoreAccountService::freezeAmount($orderIndex->storeId, 1, $orderIndex->subOrder->money, AccountLog::FREEZE_ORDER_PAY, $orderIndex->userId, $orderIndex->orderSn);
            }
            if($userId){
                $orderIndex->userId =$userId;
                $orderIndex->subOrder->userId = $userId;

                $config =  ConfigService::getChannelConfig('integralSetting', $orderIndex->uniacid,0);
                if (empty($config)) {
                    $integral = 0;
                } else {
                    $user=Member::with('vip')->where('id',$userId)->first();
                    $power = $user->vip->integralMultiplierSwitch == 1 ?  $user->vip->integralMultiplier : 1;
                    if ($config['integralState'] == 0) {
                        $integral = 0;
                    } else {
                        if ($config['giveType'] == 1) {
                            $money  = round($orderIndex->subOrder->money);
                            $int = $money *  $config['oneYuanGive'];
                        }

                        if ($config['giveType'] == 2) {
                            $int = $orderIndex->subOrder->goodsNum *  $config['onePieceGive'];
                        }

                        if ($config['giveType'] == 3) {
                            $int = $config['oneOrderGive'];
                        }
                    }
                    $integral = round($int  * $power);
                }
                $orderIndex->subOrder->integral =$integral;
//                if($integral){
//                    MemberAccountService::changeIntegral($userId, 1, $integral, MemberAccountLog::INTEGRAL_BUY_GIVE, 0, '店内订单赠送积分', $orderSn);
//                }

            }
            if (!$orderIndex->subOrder->subOrder->isEmpty()) {
                if ($userId > 0) {
                    User::updateOrCreate([
                        'uniacid' => $orderIndex->subOrder->uniacid,
                        'userId' => $userId,
                        'orderSn' => $orderIndex->subOrder->orderSn
                    ], [
                        'uniacid' => $orderIndex->subOrder->uniacid,
                        'userId' => $userId,
                        'orderSn' => $orderIndex->subOrder->orderSn
                    ]);

                }
                $subOrder = $orderIndex->subOrder->subOrder;
                $orderIndex->subOrder->isPay = 1;
                $orderIndex->subOrder->payMoney = $orderIndex->subOrder->money;
                $orderIndex->subOrder->payTime = date("Y-m-d H:i:s", time());
                // $orderIndex->subOrder->pickNo =  $orderIndex->subOrder->diningType == 4 ? '' : $orderIndex->subOrder->getPickNo();
                // $orderIndex->subOrder->pickFix = $orderIndex->subOrder->diningType == 4 ? '' : $orderIndex->subOrder->getPickFix();
                $orderIndex->subOrder->state = 2;



                $orderIndex->subOrder->save();
                if ($orderIndex->subOrder->userId == 0 && $message['userId'] > 0) {
                    $orderIndex->subOrder->userId = $message['userId'];
                    $orderIndex->subOrder->save();
                }
                $orderIndex->subOrder->orderIndex->isShow = 1;
                $orderIndex->thirdNo = $message['transaction_id'] ?? '';
                $orderIndex->payTime =  date("Y-m-d H:i:s", time());
                $orderIndex->profit_sharing = $message['profit_sharing'] ?? 0;
                $orderIndex->payType = $message['trade_type'] ?? '';
                $orderIndex->costomPayId = $message['costomPayId'] ?? 0;
                $orderIndex->payer = $message['payer']['openid'] ?? $message['payer']['sub_openid'];
                $orderIndex->payChannel = $message['payChannel'];
                $orderIndex->payTempId = $payTempId;
                $orderIndex->state = 2;
                $orderIndex->subOrder->setLog('用户已支付,等待门店接单');
                $orderIndex->save();
                OrderPay::updateOrCreate(['orderSn' => $orderIndex->orderSn], [
                    'orderSn' => $orderIndex->orderSn,
                    'prentOrderSn' => $orderIndex->orderSn,
                    'thirdNo' => $orderIndex->thirdNo,
                    'payType' => $orderIndex->payType,
                    'payTempId' => $orderIndex->payTempId,
                    'profit_sharing' => $orderIndex->profit_sharing,
                    'payChannel' => $orderIndex->payChannel,
                    'money' => $orderIndex->payType == 6 ? $message['amount'] : $orderIndex->subOrder->money,
                    'state' => 6
                ]);
                if ($orderIndex->payType == 6 && $message['amount'] > $orderIndex->subOrder->money) {
                    OrderPay::create([
                        'orderSn' => $orderIndex->orderSn,
                        'prentOrderSn' => '',
                        'thirdNo' => $orderIndex->thirdNo,
                        'payType' => PayEnum::CHANGE_RMB,
                        'payTempId' => $orderIndex->payTempId,
                        'profit_sharing' => $orderIndex->profit_sharing,
                        'payChannel' => $orderIndex->payChannel,
                        'money' => bcsub($message['amount'], $orderIndex->subOrder->money, 2),
                        'state' => 6
                    ]);
                }
            } else {
                $subOrder[] =  $orderIndex->subOrder;
                $orderIndex->subOrder->pickNo =  $orderIndex->subOrder->getPickNo();
                $orderIndex->subOrder->pickFix = $orderIndex->subOrder->getPickFix();

                $orderIndex->subOrder->save();
            }

            if ($subOrder) {
                collect($subOrder)->where('isPay', 0)->each(function ($order) use ($message, $payTempId, $orderIndex) {
                    $orderIndex->profit_sharing = $message['profit_sharing'] ?? 0;
                    $order->orderIndex->thirdNo = $message['transaction_id'] ?? '';
                    $order->orderIndex->payType = $message['trade_type'] ?? '';
                    $order->orderIndex->costomPayId = $message['costomPayId'] ?? 0;
                    $order->orderIndex->payTime =  date("Y-m-d H:i:s", time());
                    $order->orderIndex->payer = $message['payer']['openid'] ?? $message['payer']['sub_openid'];
                    $order->orderIndex->payChannel = $message['payChannel'];
                    $order->orderIndex->payTempId = $payTempId;
                    $order->orderIndex->isShow = 1;
                    $order->orderIndex->state = 2;
                    $order->isPay = $message['trade_type'] != PayEnum::RMB ? 1 : 0;
                    $order->payMoney = $message['trade_type'] != PayEnum::RMB ?  $order->money : 0;
                    $order->payTime = date("Y-m-d H:i:s", time());
                    if ($order->userId != 0) {
                    } elseif ($order->userId == 0 && $message['userId'] > 0) {
                        $order->userId = $message['userId'];
                        $order->save();
                    }
                    $userIdKey = $order->orderSn . ":userId";
                    OrderPay::updateOrCreate(['orderSn' => $orderIndex->orderSn], [
                        'orderSn' => $orderIndex->orderSn,
                        'prentOrderSn' => $order->prentOrderSn ?? $order->orderSn,
                        'thirdNo' => $order->orderIndex->thirdNo,
                        'payType' => $order->orderIndex->payType,
                        'payTempId' => $order->orderIndex->payTempId,
                        'profit_sharing' => $orderIndex->profit_sharing,
                        'payChannel' => $orderIndex->payChannel,
                        'money' => $orderIndex->payType == 6 ? $message['amount'] : $orderIndex->subOrder->money,
                        'state' => 6
                    ]);
                    if ($orderIndex->payType == 6 && $message['amount'] > $orderIndex->subOrder->money) {
                        OrderPay::create([
                            'orderSn' => $orderIndex->orderSn,
                            'prentOrderSn' => $order->prentOrderSn ?? $order->orderSn,
                            'thirdNo' => $orderIndex->thirdNo,
                            'payType' => PayEnum::CHANGE_RMB,
                            'payTempId' => $orderIndex->payTempId,
                            'profit_sharing' => $orderIndex->profit_sharing,
                            'payChannel' => $orderIndex->payChannel,
                            'money' => bcsub($message['amount'], $orderIndex->subOrder->money, 2),
                            'state' => 6
                        ]);
                    }
                    $order->orderIndex->save();
                    if ($order->payType == 1) {
                        $order->isPay = 1;
                        $order->state = 2;
                        if ($order->diningType == 4) {
                            if (empty($order->prentOrderSn)) {
                                MemberCoupon::where('id', $order->couponId)->update([
                                    'state' => 2,
                                    'updated_at' => date("Y-m-d H:i:s", time()),
                                    'orderId' => $order->id,
                                    'orderSn' => $order->orderSn
                                ]);
                                $pOrder = new Order();
                                $pOrder->fill($order->toArray());
                                $pOrder->orderSn = Cache::get($order->orderSn . ":prentOrderSn");
                                $pOrder->openTime = date("Y-m-d H:i:s", time());
                                $pOrder->pickFix = $order->pickFix;
                                $pOrder->pickNo = str_replace($order->pickFix, "", $order->pickNo);
                                $pOrder->isPay = 1;
                                $pOrder->userId = $order->userId;
                                $pOrder->service_charge = $order->service_charge;
                                $pOrder->service_money = $order->service_money;

                                $pOrder->save();
                                $pOrder->refresh();
                                $order->openTime = date("Y-m-d H:i:s", time());
                                $order->prentOrderSn = $pOrder->orderSn;
                                Table::where('id', $order->tableId)->where('state', 0)->update([
                                    'orderSn' => $order->prentOrderSn,
                                    'openTime' => date("Y-m-d H:i:s", time()),
                                ]);
                                $order->payMoney = $order->money;
                                Discount::where('orderSn', $order->orderSn)->update(['prentOrderSn' => $pOrder->orderSn]);
                                if ($order->prentOrderSn && $$order->userId > 0) {
                                    User::updateOrCreate([
                                        'uniacid' => $order->uniacid,
                                        'userId' => $order->userId,
                                        'orderSn' => $order->prentOrderSn
                                    ], [
                                        'uniacid' => $order->uniacid,
                                        'userId' => $order->userId,
                                        'orderSn' => $order->prentOrderSn
                                    ]);
                                }
                                OrderPay::where('orderSn', $order->orderSn)->update(["prentOrderSn" => $pOrder->orderSn]);
                            }

                            $order->save();
                            $order->refresh();
                            if ($order->diningType == 4) {
                                $lockKey = 'InstoreCheckout:' . $order->uniacid . $order->storeId . $order->tableId . $order->diningType;
                            } else {
                                $lockKey = 'InstoreCheckout:' . $order->uniacid . $order->storeId . $order->tableId . $order->diningType . $order->userId;
                            }
                            optional(Cache::lock($lockKey))->forceRelease();
                            Cache::delete('InstoreCheckout:' . $order->userId);
                        }
                        $goodsKey = $order->orderSn . ":goodsList";
                        if (Cache::has($goodsKey)) {
                            foreach (Cache::get($goodsKey) as $key => $goods) {
                                $goodsItem = $goods->toArray();
                                unset($goodsItem['id']);
                                $goodsItem['name'] = $goods->goods->name;
                                $goodsItem['logo'] = $goods->goods->logo;
                                $goodsItem['orderSn'] = $order->orderSn;
                                $goodsItem['prentOrderSn'] = $order->prentOrderSn;
                                $orderGoods[] = new OrderGoods($goodsItem);
                                $order->goodsNum = $order->goodsNum + $goodsItem['num'];
                                $goods->delete();
                            }
                            $tradeinGoodsDataKey = $order->orderSn . ":tradeinGoodsData";
                            if (Cache::has($tradeinGoodsDataKey)) {
                                foreach (Cache::get($tradeinGoodsDataKey) as $key => $goods) {
                                    $goodsItem = $goods->toArray();
                                    unset($goodsItem['id']);
                                    $goodsItem['name'] = $goods->goods->name;
                                    $goodsItem['logo'] = $goods->goods->logo;
                                    $goodsItem['orderSn'] = $order->orderSn;
                                    $goodsItem['prentOrderSn'] = $order->prentOrderSn;
                                    $orderGoods[] = new OrderGoods($goodsItem);
                                    $order->goodsNum = $order->goodsNum + $goodsItem['num'];
                                    $goods->delete();
                                }
                            }
                            $order->goods()->saveMany($orderGoods);
                        }
                        $order->save();
                        if ($order->prentOrderSn) {
                            $order->perentOrder->refresh();
                            $order->perentOrder->isPay = 1;
                            $order->perentOrder->state = $order->state;
                            $order->perentOrder->orderIndex->isShow = 1;
                            $order->perentOrder->changeData();
                        }
                        if ($order->autoReceive == 1) {
                            InStoreOrderService::received($order->id, '门店自动接单，商品制作中');
                        } else {
                            if ($order->diningType == 4) {
                                Event(new StoreMessageEvent($order->orderIndex, 'inStoreNewOrder'));
                            } else {
                                Event(new StoreMessageEvent($order->orderIndex, 'newOrder'));
                            }
                        }
                    } else {
                        if ($order->userId > 0) {
                            Member::where('id', $order->userId)->increment('payOrder');
                        } elseif ($order->userId == 0 && $message['userId'] > 0) {
                            $order->userId = $message['userId'];
                            $order->save();
                            Member::where('id', $order->userId)->increment('payOrder');
                        }
                        $order->state = $order->state > 2 ? $order->state : 2;
                        $order->payMoney = $order->money;
                        $order->save();
                        if ($order->prentOrderSn) {
                            $order->perentOrder->openTime = $order->table->openTime;
                            $order->perentOrder->state = $order->state;

                            $order->perentOrder->service_charge = $order->service_charge;
                            $order->perentOrder->service_money = $order->service_money;
                            $order->perentOrder->changeData();
                        }
                    }
                    $order->save();
                });
            }
            if ($orderIndex->isSub == 0 && $orderIndex->subOrder->payType == 2 && $orderIndex->subOrder->diningType == 4) {
                InStoreOrderService::complete($orderIndex->subOrder->id);
            }
            Event(new OrderMessageEvent($orderIndex->subOrder, 'pay'));
            //Event(new PartnerEvent($orderIndex->subOrder));
            return true;
        } catch (\Exception $e) {
            file_put_contents('jspay.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }

    public static function pointsMail($message, $orderSn, $payTempId = 1, $userId = 0)
    {
        try {
            $orderIndex = OrderIndex::where('type', 5)->unpaid()->where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                return false;
            }
            $orderIndex->thirdNo = $message['transaction_id'] ?? '';
            $orderIndex->payType = $message['trade_type'] ?? '';
            $orderIndex->payTime =  Carbon::now()->toDateTimeString();
            $orderIndex->profit_sharing = $message['profit_sharing'] ?? 0;
            $orderIndex->payer = $message['payer']['openid'] ?? $message['payer']['sub_openid'];
            $orderIndex->payChannel = $message['payChannel'];
            $orderIndex->payTempId = $payTempId;
            $orderIndex->user->save();
            if ($orderIndex->subOrder->points > 0 && $orderIndex->subOrder->money > 0) {
                $res = MemberAccountService::pointsPay($orderIndex->orderSn, $orderIndex->userId);
                if (!$res) {
                    $order = [
                        'takeOutNo' => $orderIndex->orderSn,
                        'openid' => $orderIndex->payer,
                        'transaction_id' => $orderIndex->thirdNo,
                        'total_amount' => $orderIndex->subOrder->money,
                        'refund_amount' => $orderIndex->subOrder->money,
                    ];
                    $refundOrder = RefundOrder::where('takeOutNo', $orderIndex->orderSn)->first();
                    if (empty($refundOrder)) {
                        $res = PayService::refund($order, $orderIndex->uniacid, $orderIndex->payTempId);
                        if (!$res) {
                            DB::rollBack();
                            return true;
                        }
                    }
                }
                OrderPay::create([
                    'orderSn' => $orderIndex->orderSn,
                    'uniaicd' => $orderIndex->uniacid,
                    'prentOrderSn' => $orderIndex->orderSn,
                    'thirdNo' => null,
                    'payType' => PayEnum::POINTS,
                    'payTempId' => 0,
                    'profit_sharing' => 0,
                    'payChannel' => 1,
                    'money' => $orderIndex->subOrder->points,
                    'state' => 6
                ]);
            }
            $orderIndex->state = 2;
            $orderIndex->save();
            OrderPay::updateOrCreate(['orderSn' => $orderIndex->orderSn], [
                'orderSn' => $orderIndex->orderSn,
                'prentOrderSn' => $orderIndex->orderSn,
                'thirdNo' => $orderIndex->thirdNo,
                'money' => $orderIndex->payMoney,
                'payType' => $orderIndex->payType,
                'payTempId' => $orderIndex->payTempId,
                'profit_sharing' => $orderIndex->profit_sharing,
                'payChannel' => $orderIndex->payChannel,
                'state' => 6
            ]);
            $orderIndex->subOrder->payTime = Carbon::now()->toDateTimeString();
            $orderIndex->subOrder->state = 2;
            $orderIndex->subOrder->qrCode =  $orderIndex->subOrder->diningType == 2 ? rand(10000000, 99999999) : '';
            $orderIndex->subOrder->save();

            $goods = $orderIndex->subOrder->goods;
            DB::table('points_mall')->where('id', $goods['id'])->increment('sales', 1);
            DB::table('points_mall')->where('id', $goods['id'])->decrement('stock', 1);
            if ($goods['product_type'] == 1) {
                return true;
            }else{
                dispatch(new PointsGoodsJob($orderIndex->subOrder));
            }
            return true;
        } catch (\Exception $e) {
            file_put_contents('jspay.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }
    public static function couponPack($message, $orderSn, $payTempId = 1, $userId = 0)
    {
        try {
            $orderIndex = OrderIndex::where('type', 6)->unpaid()->where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                return false;
            }
            $orderIndex->thirdNo = $message['transaction_id'] ?? '';
            $orderIndex->payType = $message['trade_type'] ?? '';
            $orderIndex->payTime =  Carbon::now()->toDateTimeString();
            $orderIndex->profit_sharing = $message['profit_sharing'] ?? 0;
            $orderIndex->payer = $message['payer']['openid'] ?? $message['payer']['sub_openid'];
            $orderIndex->payChannel = $message['payChannel'];
            $orderIndex->payTempId = $payTempId;
            $orderIndex->state = 6;
            $orderIndex->save();
            $orderIndex->subOrder->payTime = Carbon::now()->toDateTimeString();
            $orderIndex->subOrder->state = 6;
            $orderIndex->subOrder->save();
            $orderIndex->user->save();
            OrderPay::updateOrCreate(['orderSn' => $orderIndex->orderSn], [
                'orderSn' => $orderIndex->orderSn,
                'prentOrderSn' => $orderIndex->orderSn,
                'thirdNo' => $orderIndex->thirdNo,
                'money' => $orderIndex->payMoney,
                'payType' => $orderIndex->payType,
                'payTempId' => $orderIndex->payTempId,
                'profit_sharing' => $orderIndex->profit_sharing,
                'payChannel' => $orderIndex->payChannel,
                'state' => 6
            ]);
            CouponService::issue($orderIndex->subOrder->couponGive, $orderIndex->userId, 14, [
                'source' => "couponPackOrder:{$orderIndex->suborder->id}",
            ]);
            DB::table('coupon_pack')->where('id', $orderIndex->subOrder->couponPackId)->increment('sales', 1);
            DB::table('coupon_pack')->where('id', $orderIndex->subOrder->couponPackId)
                ->where('inventoryType', 1)
                ->decrement('inventory', 1);
            $limitKey = "couponPackOrder:userlimit:{$orderIndex->subOrder->couponPackId}:{$orderIndex->subOrder->userId}";
            Cache::increment($limitKey, 1);
            $dayLimitKey = "couponPackOrder:userDaylimit:{$orderIndex->subOrder->couponPackId}:" . date("Ymd") . ":{$orderIndex->subOrder->userId}";
            Cache::increment($dayLimitKey, 1);
            return true;
        } catch (\Exception $e) {
            file_put_contents('jspay.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }

    public static function tableReserve($message, $orderSn, $payTempId = 1, $userId = 0)
    {
        try {
            $orderIndex = OrderIndex::where('type', 7)->unpaid()->where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                return false;
            }
            $orderIndex->thirdNo = $message['transaction_id'] ?? '';
            $orderIndex->payType = $message['trade_type'] ?? '';
            $orderIndex->payTime =  Carbon::now()->toDateTimeString();
            $orderIndex->profit_sharing = $message['profit_sharing'] ?? 0;
            $orderIndex->payer = $message['payer']['openid'] ?? $message['payer']['sub_openid'];
            $orderIndex->payChannel = $message['payChannel'];
            $orderIndex->payTempId = $payTempId;
            $orderIndex->state = 2;
            $orderIndex->save();
            $orderIndex->user->save();
            if ($orderIndex->payChannel == 1 && $orderIndex->subOrder->money > 0) {
                StoreAccountService::freezeAmount($orderIndex->storeId, 1, $orderIndex->subOrder->money, AccountLog::FREEZE_ORDER_PAY, $orderIndex->userId, $orderIndex->orderSn);
            }
            OrderPay::updateOrCreate(['orderSn' => $orderIndex->orderSn], [
                'orderSn' => $orderIndex->orderSn,
                'prentOrderSn' => $orderIndex->orderSn,
                'thirdNo' => $orderIndex->thirdNo,
                'money' => $orderIndex->subOrder->payMoney,
                'payType' => $orderIndex->payType,
                'payTempId' => $orderIndex->payTempId,
                'profit_sharing' => $orderIndex->profit_sharing,
                'payChannel' => $orderIndex->payChannel,
                'state' => 6
            ]);
            $orderIndex->subOrder->payTime = Carbon::now()->toDateTimeString();
            $orderIndex->subOrder->state = 2;
            if ($orderIndex->subOrder->autoReceive == 1) {
                $orderIndex->subOrder->state = 3;
                $orderIndex->subOrder->reserveTime = Carbon::now()->toDateTimeString();
            }
            $orderIndex->subOrder->save();
            return true;
        } catch (\Exception $e) {
            file_put_contents('jspay.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }

    public static function equityCard($message, $orderSn, $payTempId = 1, $userId = 0)
    {
        try {
            $orderIndex = OrderIndex::where('type', 8)->unpaid()->where('orderSn', $orderSn)->first();
            if (empty($orderIndex)) {
                return false;
            }
            $orderIndex->thirdNo = $message['transaction_id'] ?? '';
            $orderIndex->payType = $message['trade_type'] ?? '';
            $orderIndex->payTime =  Carbon::now()->toDateTimeString();
            $orderIndex->profit_sharing = $message['profit_sharing'] ?? 0;
            $orderIndex->payer = $message['payer']['openid'] ?? $message['payer']['sub_openid'];
            $orderIndex->payChannel = $message['payChannel'];
            $orderIndex->payTempId = $payTempId;
            $orderIndex->state = 6;
            $orderIndex->save();
            OrderPay::updateOrCreate(['orderSn' => $orderIndex->orderSn], [
                'orderSn' => $orderIndex->orderSn,
                'prentOrderSn' => $orderIndex->orderSn,
                'thirdNo' => $orderIndex->thirdNo,
                'money' => $orderIndex->payMoney,
                'payType' => $orderIndex->payType,
                'payTempId' => $orderIndex->payTempId,
                'profit_sharing' => $orderIndex->profit_sharing,
                'payChannel' => $orderIndex->payChannel,
                'state' => 6
            ]);
            $member = equityCardMember::create([
                'orderSn' => $orderIndex->orderSn,
                'uniacid' => $orderIndex->uniacid,
                'storeId' => $orderIndex->storeId,
                'userId' => $orderIndex->userId,
                'equityCardId' => $orderIndex->subOrder->equityCardId,
                'startTime' => Carbon::now()->toDateTimeString(),
                'endTime' => Carbon::now()->addDays($orderIndex->subOrder->equityCard->day)->toDateTimeString(),
            ]);
            $orderIndex->subOrder->startTime = $member->startTime;
            $orderIndex->subOrder->endTime = $member->endTime;
            $orderIndex->subOrder->payTime = Carbon::now()->toDateTimeString();
            $orderIndex->subOrder->state = 6;
            $orderIndex->subOrder->save();
            event(new EquityCardEvent($member));
            return true;
        } catch (\Exception $e) {
            file_put_contents('jspay.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }
}
