<?php

namespace App\Services;

use App\Models\ChannelConfig;
use App\Models\TopLevel;
use App\Traits\ResourceTrait;
use App\Models\Config;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Store;
use App\Models\Drag;
use App\Models\Member\Vip;
use App\Models\OpenWechatAuth;
use App\Models\StoreConfig;
use App\Models\VoiceMessage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Models\LuckyWheel;
class DataSeederService
{
    public static function applyLuckyWheelSeed($uniacid = 0)
    {
        try {
            $data = array(
                'uniacid' => $uniacid,
                'state' => 0,   //关闭
                'acquireType' => 0,
                'count' => 1,
                'storeType' => 1,   //全部
                'storeIds' => '[]',
                'desc' => "中奖后请联系客服领取",
                'popPic' => "https://vv3img.icall.me/1/uploads/2024/08/23/202408231734572837.png?x-oss-process=style/qiance",
                'threshold' => "10.00",
                'acquireMethods' => '[0,1,2,3]',
                'created_at' => date("Y-m-d H:i:s", time()),
                'updated_at' =>  date("Y-m-d H:i:s", time())
            );
            LuckyWheel::where('uniacid', $uniacid)
                ->delete();
            LuckyWheel::insert($data);
        } catch (\Exception $e) {
            return false;
        }
    }
    public  static function applyConfigSeed($uniacid = 0, $storeId = 0)
    {
        try {
            $data =  array(
                0 =>
                array(
                    'data' => '{"goState":1,"shopKm":"3","changState":1,"pageState":1,"showLabelState":0,"orderInfoState":1,"storeId":2,"km":"10","ident":"storeBasicSetting","identName":"u95e8u5e97u57fau672cu8bbeu7f6e","uniacid":1}',
                    'uniacid' => 0,
                    'ident' => 'storeBasicSetting',
                    'name' => '门店基本设置',
                    'created_at' => '2023-04-07 15:10:36',
                    'updated_at' => '2023-04-08 09:48:16',
                    'deleted_at' => NULL,
                ),
                1 =>
                array(
                    'data' => '{"integralState":0,"giveType":1,"giveTime":2,"oneYuanGive":"1","onePieceGive":"1","oneOrderGive":"1","ident":"integralSetting","identName":"积分设置","uniacid":1}',
                    'uniacid' => 0,
                    'ident' => 'integralSetting',
                    'name' => '积分设置',
                    'created_at' => '2023-04-07 21:06:09',
                    'updated_at' => '2023-04-08 10:03:59',
                    'deleted_at' => NULL,
                ),
                2 =>
                array(
                    'data' => '{"growthState":1,"giveType":1,"giveTime":1,"checkList":["phone","nickname","birthday","realname","sexual"],"oneYuanGive":"1","onePieceGive":"1","oneOrderGive":"1","ident":"growthSetting","identName":"u6210u957fu503cu8bbeu7f6e","uniacid":1}',
                    'uniacid' => 0,
                    'ident' => 'growthSetting',
                    'name' => '成长值设置',
                    'created_at' => '2023-04-07 21:06:22',
                    'updated_at' => '2023-04-08 09:54:25',
                    'deleted_at' => NULL,
                ),
                3 =>
                array(
                    'data' => '{"orderPriority":1,"onAutoOrder":1,"autoOrder":[1,2],"userOverTime":10,"shopOverTime":10,"onSelfOrder":1,"chSelfType":[1,2],"selfTakingTime":10,"selfAccomplishTime":29,"selfAppointTime":10,"selfAppAccomplishTime":29,"onTakeOutOrder":1,"chTakeOutType":[1,2],"takeOutTakingTime":5,"takeOutAccomplishTime":29,"takeOutAppointTime":5,"takeOutAppAccomplishTime":29,"onSelfOrderWrite":2,"daySelfStyle":[1],"paySkip":1,"shopDistance":3,"onShopCheck":1,"onOrderRemarks":2,"onSelfRemarks":2,"selfDefaultPrompt":null,"onTakeOutRemarks":2,"takeOutDefaultPrompt":null,"onUserAllowRefunds":2,"onShopAllowRefunds":2,"onShopAllowRefundsType":[],"onShopRefundReview":2,"onSelfRefundReview":2,"refundTime":null,"onOvertime":2,"onRefundReason":1,"ident":"orderSetting","identName":"订单设置","uniacid":1,"orderForm":{"askFor":"Z","subscribe":"Z","besides":"W","besidesSub":"W","pay":null,"eatIn":null},"orderType":1,"initialNum":1,"fixation":1,"finishNum":999,"drawback":["临时有事，我不想要了","买错了/买多了/买少了","忘点主食","忘记写备注","地址/电话填写错误","商家缺货，联系我取消订单"]}',
                    'uniacid' => 0,
                    'ident' => 'orderSetting',
                    'name' => '订单设置',
                    'created_at' => '2023-04-08 09:47:19',
                    'updated_at' => '2023-04-08 09:47:19',
                    'deleted_at' => NULL,
                ),
                4 =>
                array(
                    'data' => '{"state":0,"topUpPrice":0,"minPrice":null,"storeType":1,"storeId":[],"ruleContent":null,"agreementContent":null,"bgImage":null,"ruleState":1,"ident":"storageVal","identName":"储值设置","uniacid":1}',
                    'uniacid' => 0,
                    'ident' => 'storageVal',
                    'name' => '储值设置',
                    'created_at' => '2023-04-08 10:03:32',
                    'updated_at' => '2023-04-08 10:03:32',
                    'deleted_at' => NULL,
                ),
                array(
                    'data' => '{"mapType":"tx","txKey":null,"relation":[2],"relService":1,"aboutUs":null,"agreementTitle":null,"agreementCon":null,"ident":"basicSetting","identName":"\u57fa\u672c\u8bbe\u7f6e"}',
                    'uniacid' => 0,
                    'ident' => 'basicSetting',
                    'name' => '基本设置',
                    'created_at' => '2023-04-08 10:03:32',
                    'updated_at' => '2023-04-08 10:03:32',
                    'deleted_at' => NULL,
                ),
                array(
                    'data' => '{"onFastOrder":1,"fastTakingTime":5,"orderType":1,"orderForm":{"fastfood":"Q","meals":"T","order":"T"},"fixation":1,"finishNum":999,"onUserAllowRefunds":2,"onRefundReason":2,"drawback":["未按要求制作","上菜比较慢","点错了/点多了","未按要求制作","商品缺货，商家联系我取消"],"onOrderRemarks":1,"takeOutDefaultPrompt":"请填写您的特殊需求","ident":"inStoreOrderConfig","identName":"店内设置","uniacid":1}',
                    'uniacid' => 0,
                    'ident' => 'inStoreOrderConfig',
                    'name' => '店内设置',
                    'created_at' => '2023-04-08 10:03:32',
                    'updated_at' => '2023-04-08 10:03:32',
                    'deleted_at' => NULL,
                ),
                array(
                    'data' => '{"loginSwitch":2,"balanceSwitch":1,"couponSwitch":2,"orderType":1,"payEvaluate":2,"orderForm":{"personPay":"D"},"fixation":1,"finishNum":999,"ident":"personPayOrderConfig","identName":"当面付设置"}',
                    'uniacid' => 0,
                    'ident' => 'personPayOrderConfig',
                    'name' => '当面付设置',
                    'created_at' => '2023-04-08 10:03:32',
                    'updated_at' => '2023-04-08 10:03:32',
                    'deleted_at' => NULL,
                ),
                array(
                    'data' => '{"give":["友情赠送","赠送活动","顾客投诉质量问题"],"goodsDiscount":["友情打折","临时活动","顾客投诉质量问题"],"orderNotes":["全单少辣","全单少油","全单少盐","全单少糖","不加葱","不加香菜"],"orderDiscount":["友情打折","临时活动","顾客投诉质量问题"],"goodsNotes":["少辣","少糖","少盐","少油","不加葱","不加香菜"],"orderFree":["友情打折","临时活动","顾客投诉质量问题"],"backGoods":["未按要求制作","不新鲜","发现异物","上菜慢","错点","多点","估清"]}',
                    'uniacid' => 0,
                    'ident' => 'reasonConfig',
                    'name' => '当面付设置',
                    'created_at' => '2023-04-08 10:03:32',
                    'updated_at' => '2023-04-08 10:03:32',
                    'deleted_at' => NULL,
                )
            );
            $data = collect($data)->map(function ($item) use ($uniacid, $storeId) {
                return collect($item)
                    ->put('uniacid', $uniacid)
                    ->put('storeId', $storeId)
                    ->put('created_at', date("Y-m-d H:i:s", time()))
                    ->put('updated_at', date("Y-m-d H:i:s", time()));
            })->toArray();
            ChannelConfig::insert($data);
        } catch (\Exception) {
            return false;
        }
    }

    public static function applyDeliveryRuleSeed($uniacid = 0)
    {
        try {
            $data =  array(
                0 =>
                array(
                    'uniacid' => 0,
                    'name' => '配送模版默认',
                    'desc' => '配送模版默认设置，可自行自定义设置',
                    'channel' => '[]',
                    'deliveryType' => 2,
                    'deliveryData' => '[]',
                    'receivingMinutes' => 5,
                    'advanceOrderMinutes' => 10,
                    'advanceOrderType' => 1,
                    'loseType' => 1,
                    'loseNum' => 3,
                    'kmMinutes' => 30,
                    'kmPushMinutes' => 5,
                    'km' => 3,
                    'priceType' => 1,
                    'priceFixData' => '{"money":"5"}',
                    'priceDistanceData' => '{"startKm":3,"startMoney":5,"pushKm":1,"pushMoney":1}',
                    'priceAreaData' => '[]',
                    'created_at' => date("Y-m-d H:i:s", time()),
                    'updated_at' => date("Y-m-d H:i:s", time()),
                    'deleted_at' => NULL,
                    'startRule' => '{"type":1,"value":1}',
                    'estimate' => 1,
                    'callType' => 1,
                    'makeMinutes' => 5,
                ),
            );
            $data = collect($data)->map(function ($item) use ($uniacid) {
                return collect($item)
                    ->put('uniacid', $uniacid)
                    ->put('created_at', date("Y-m-d H:i:s", time()))
                    ->put('updated_at', date("Y-m-d H:i:s", time()));
            })->toArray();
            Rule::insert($data);
        } catch (\Exception) {
            return false;
        }
    }


    public static function applyVipSeed($uniacid = 0, $storeId = 0)
    {
        try {
            $data =  array(
                0 =>
                array(
                    'uniacid' => 1,
                    'level' => 1,
                    'storeId' => 0,
                    'name' => '默认等级',
                    'styleSwitch' => 0,
                    'style' => NULL,
                    'exp' => 0,
                    'balanceSwitch' => 0,
                    'balance' => '0.01',
                    'integralSwitch' => 0,
                    'integral' => 1,
                    'discountSwitch' => 0,
                    'discount' => '9.0',
                    'integralMultiplierSwitch' => 0,
                    'integralMultiplier' => '1.00',
                    'freeMailSwitch' => 0,
                    'freeMailLimit' => '20.00',
                    'created_at' => '2023-04-07 21:05:56',
                    'updated_at' => '2023-04-08 09:55:58',
                    'deleted_at' => NULL,
                ),
                1 =>
                array(
                    'uniacid' => 1,
                    'level' => 2,
                    'storeId' => 0,
                    'name' => '青铜',
                    'styleSwitch' => 0,
                    'style' => NULL,
                    'exp' => 1000,
                    'balanceSwitch' => 0,
                    'balance' => '0.01',
                    'integralSwitch' => 0,
                    'integral' => 1,
                    'discountSwitch' => 0,
                    'discount' => '0.0',
                    'integralMultiplierSwitch' => 0,
                    'integralMultiplier' => '0.01',
                    'freeMailSwitch' => 0,
                    'freeMailLimit' => '0.01',
                    'created_at' => '2023-04-07 21:09:57',
                    'updated_at' => '2023-04-08 09:56:46',
                    'deleted_at' => NULL,
                ),
                2 =>
                array(
                    'uniacid' => 1,
                    'level' => 3,
                    'name' => '白银',
                    'styleSwitch' => 0,
                    'storeId' => 0,
                    'style' => NULL,
                    'exp' => 2000,
                    'balanceSwitch' => 0,
                    'balance' => '0.00',
                    'integralSwitch' => 0,
                    'integral' => 0,
                    'discountSwitch' => 0,
                    'discount' => '0.0',
                    'integralMultiplierSwitch' => 0,
                    'integralMultiplier' => '0.00',
                    'freeMailSwitch' => 0,
                    'freeMailLimit' => '0.00',
                    'created_at' => '2023-04-08 09:57:21',
                    'updated_at' => '2023-04-08 09:57:21',
                    'deleted_at' => NULL,
                ),
                3 =>
                array(
                    'uniacid' => 1,
                    'level' => 4,
                    'name' => '黄金',
                    'styleSwitch' => 0,
                    'style' => NULL,
                    'storeId' => 0,
                    'exp' => 3000,
                    'balanceSwitch' => 0,
                    'balance' => '0.01',
                    'integralSwitch' => 0,
                    'integral' => 1,
                    'discountSwitch' => 0,
                    'discount' => '0.0',
                    'integralMultiplierSwitch' => 0,
                    'integralMultiplier' => '0.01',
                    'freeMailSwitch' => 0,
                    'freeMailLimit' => '0.01',
                    'created_at' => '2023-04-08 09:58:06',
                    'updated_at' => '2023-04-08 09:58:14',
                    'deleted_at' => NULL,
                ),
                4 =>
                array(
                    'uniacid' => 1,
                    'level' => 5,
                    'name' => '铂金',
                    'styleSwitch' => 0,
                    'style' => NULL,
                    'exp' => 4000,
                    'balanceSwitch' => 0,
                    'balance' => '0.00',
                    'storeId' => 0,
                    'integralSwitch' => 0,
                    'integral' => 0,
                    'discountSwitch' => 0,
                    'discount' => '0.0',
                    'integralMultiplierSwitch' => 0,
                    'integralMultiplier' => '0.00',
                    'freeMailSwitch' => 0,
                    'freeMailLimit' => '0.00',
                    'created_at' => '2023-04-08 09:58:50',
                    'updated_at' => '2023-04-08 09:58:50',
                    'deleted_at' => NULL,
                ),
                5 =>
                array(
                    'uniacid' => 1,
                    'level' => 6,
                    'name' => '钻石',
                    'storeId' => 0,
                    'styleSwitch' => 0,
                    'style' => NULL,
                    'exp' => 5000,
                    'balanceSwitch' => 0,
                    'balance' => '0.00',
                    'integralSwitch' => 0,
                    'integral' => 0,
                    'discountSwitch' => 0,
                    'discount' => '0.0',
                    'integralMultiplierSwitch' => 0,
                    'integralMultiplier' => '0.00',
                    'freeMailSwitch' => 0,
                    'freeMailLimit' => '0.00',
                    'created_at' => '2023-04-08 09:59:07',
                    'updated_at' => '2023-04-08 09:59:07',
                    'deleted_at' => NULL,
                ),
                6 =>
                array(
                    'uniacid' => 1,
                    'level' => 7,
                    'name' => '宗师',
                    'styleSwitch' => 0,
                    'style' => NULL,
                    'storeId' => 0,
                    'exp' => 6000,
                    'balanceSwitch' => 0,
                    'balance' => '0.01',
                    'integralSwitch' => 0,
                    'integral' => 1,
                    'discountSwitch' => 0,
                    'discount' => '0.0',
                    'integralMultiplierSwitch' => 0,
                    'integralMultiplier' => '0.01',
                    'freeMailSwitch' => 0,
                    'freeMailLimit' => '0.01',
                    'created_at' => '2023-04-08 09:59:42',
                    'updated_at' => '2023-04-08 10:00:18',
                    'deleted_at' => NULL,
                ),
                7 =>
                array(
                    'uniacid' => 1,
                    'level' => 8,
                    'name' => '王者',
                    'styleSwitch' => 0,
                    'style' => NULL,
                    'exp' => 7000,
                    'balanceSwitch' => 0,
                    'balance' => '0.01',
                    'storeId' => 0,
                    'integralSwitch' => 0,
                    'integral' => 1,
                    'discountSwitch' => 0,
                    'discount' => '0.0',
                    'integralMultiplierSwitch' => 0,
                    'integralMultiplier' => '0.01',
                    'freeMailSwitch' => 0,
                    'freeMailLimit' => '0.01',
                    'created_at' => '2023-04-08 10:00:07',
                    'updated_at' => '2023-04-08 10:00:24',
                    'deleted_at' => NULL,
                ),
                8 =>
                array(
                    'uniacid' => 1,
                    'level' => 9,
                    'name' => '星耀',
                    'styleSwitch' => 0,
                    'storeId' => 0,
                    'style' => NULL,
                    'exp' => 8000,
                    'balanceSwitch' => 0,
                    'balance' => '0.00',
                    'integralSwitch' => 0,
                    'integral' => 0,
                    'discountSwitch' => 0,
                    'discount' => '0.0',
                    'integralMultiplierSwitch' => 0,
                    'integralMultiplier' => '0.00',
                    'freeMailSwitch' => 0,
                    'freeMailLimit' => '0.00',
                    'created_at' => '2023-04-08 10:01:01',
                    'updated_at' => '2023-04-08 10:01:01',
                    'deleted_at' => NULL,
                ),
                9 =>
                array(
                    'uniacid' => 1,
                    'level' => 10,
                    'name' => '巅峰',
                    'styleSwitch' => 0,
                    'style' => NULL,
                    'exp' => 9000,
                    'balanceSwitch' => 0,
                    'balance' => '0.00',
                    'integralSwitch' => 0,
                    'storeId' => 0,
                    'integral' => 0,
                    'discountSwitch' => 0,
                    'discount' => '0.0',
                    'integralMultiplierSwitch' => 0,
                    'integralMultiplier' => '0.00',
                    'freeMailSwitch' => 0,
                    'freeMailLimit' => '0.00',
                    'created_at' => '2023-04-08 10:02:12',
                    'updated_at' => '2023-04-08 10:02:12',
                    'deleted_at' => NULL,
                ),
            );
            $data = collect($data)->map(function ($item) use ($uniacid, $storeId) {
                return collect($item)->put('uniacid', $uniacid)
                    ->put('storeId', $storeId)
                    ->put('created_at', date("Y-m-d H:i:s", time()))
                    ->put('updated_at', date("Y-m-d H:i:s", time()));
            })->toArray();
            Vip::insert($data);
        } catch (\Exception) {
            return false;
        }
    }

    public static function StoreConfigSeed($uniacid = 0, $storeId)
    {
        try {
            $data =  array(
                0 =>
                array(
                    'data' => '{"takeOrder":1,"takeLiftState":1,"outOrderState":2,"pickupSwitch":"1","takeoutSwitch":"1","takeSubscribe":1,"takeDistanceTipState":1,"takePredictTime":2,"outAppoint":1,"takeEatType":[1,2],"takeMakeTime":"20","outTimeData":{"week":[1,3,5,6,7,0],"times":[{"start":"02:15","end":"23:45","ciri":false}]},"outMakeTime":"15","takeLiftPrice":"0","storeId":"4","ident":"storeSetting","identName":"\u81ea\u53d6\/\u5916\u9001\u8bbe\u7f6e","takeDistance":"20","takeCloseEat":2,"takeSubscribeNum":1,"takeTimeStep":60,"takeAppointTimeStep":[1,2],"takePrintTime":[1,2],"takeOrderTime":[1,2],"takeBeforTime":"30","takeWaitTime":"100","outCloseImmediateDine":0,"outPrintTime":[1,2],"outStepTime":[1,2],"outAppointDay":"0","outTimeStep":5,"outBeforPrint":"15"}',
                    'storeId' => 1,
                    'ident' => 'storeSetting',
                    'name' => '自取/外送设置',
                    'created_at' => '2023-04-07 14:47:36',
                    'updated_at' => '2023-04-08 09:52:44',
                    'deleted_at' => NULL,
                ),
                array(
                    'data' => '{"pickupSwitch":1,"orderMode":[1,3],"order":{"setting":1,"payMode":2,"verification":1,"receive":1,"attendant":1,"cleanTime":30},"delivery":{"setting":1,"payMode":1,"receive":2,"cleanTime":30},"callNum":{"setting":1,"payMode":1,"receive":2,"state":1},"style":1,"confirm":2,"drawing":0,"storeId":"1","ident":"inStoreSetting","identName":"\u5e97\u5185\u8bbe\u7f6e"}',
                    'storeId' => 1,
                    'ident' => 'inStoreSetting',
                    'name' => '店内设置',
                    'created_at' => '2023-04-07 14:47:36',
                    'updated_at' => '2023-04-08 09:52:44',
                    'deleted_at' => NULL,
                ),
                array(
                    'data' => '{"score":[1,2],"callNum":2,"ident":"takeScreen","identName":"\u53eb\u53f7\u8bbe\u7f6e","storeId":"1"}',
                    'storeId' => 1,
                    'ident' => 'takeScreen',
                    'name' => '叫号设置',
                    'created_at' => '2023-04-07 14:47:36',
                    'updated_at' => '2023-04-08 09:52:44',
                    'deleted_at' => NULL,
                ),
                array(
                    'data' => '{"style":2,"ident":"takeScreenStyle","identName":"\u5927\u5c4f\u5e55\u8bbe\u7f6e","storeId":"1"}',
                    'storeId' => 1,
                    'ident' => 'takeScreenStyle',
                    'name' => '大屏幕设置',
                    'created_at' => '2023-04-07 14:47:36',
                    'updated_at' => '2023-04-08 09:52:44',
                    'deleted_at' => NULL,
                )
            );
            $data = collect($data)->map(function ($item) use ($uniacid, $storeId) {
                return collect($item)
                    ->put('storeId', $storeId)
                    ->put('created_at', date("Y-m-d H:i:s", time()))
                    ->put('updated_at', date("Y-m-d H:i:s", time()));
            })->toArray();
            StoreConfig::insert($data);
        } catch (\Exception) {
            return false;
        }
    }

    public  static function StoreDeliverySeed($uniacid = 0, $storeId)
    {
        try {
            $data =  array(
                0 =>
                array(
                    'uniacid' => 0,
                    "storeId" => 0,
                    'ruleId' => 0,
                    'name' => '配送模版默认',
                    'desc' => '配送模版默认设置，可自行自定义设置',
                    'channel' => '[]',
                    'deliveryType' => 2,
                    'deliveryData' => '[]',
                    'receivingMinutes' => 5,
                    'advanceOrderMinutes' => 10,
                    'advanceOrderType' => 1,
                    'loseType' => 1,
                    'loseNum' => 3,
                    'kmMinutes' => 30,
                    'kmPushMinutes' => 5,
                    'km' => 3,
                    'priceType' => 1,
                    'priceFixData' => '{"money":"5"}',
                    'priceDistanceData' => '{"startKm":3,"startMoney":5,"pushKm":1,"pushMoney":1}',
                    'priceAreaData' => '[]',
                    'created_at' => date("Y-m-d H:i:s", time()),
                    'updated_at' => date("Y-m-d H:i:s", time()),
                    'deleted_at' => NULL,
                    'startRule' => '{"type":1,"value":1}',
                    'estimate' => 1,
                    'callType' => 1,
                    'makeMinutes' => 5,
                ),
            );
            $data = collect($data)->map(function ($item) use ($uniacid, $storeId) {
                return collect($item)
                    ->put('uniacid', $uniacid)
                    ->put('storeId', $storeId)
                    ->put('created_at', date("Y-m-d H:i:s", time()))
                    ->put('updated_at', date("Y-m-d H:i:s", time()));
            })->toArray();
            Store::insert($data);
        } catch (\Exception) {
            return false;
        }
    }


    public static function dragSeed($uniacid, $storeId = 0)
    {
        $data = array(
            0 =>
            array(
                'title' => '店铺首页',
                'uniacid' => 52,
                'type' => NULL,
                'data' => '{"list":[{"title":"图片轮播","name":"picLunbo","hide":1,"styles":{"marginTop":0,"marginBottom":-35,"marginLR":0,"tCircle":10,"bCircle":10,"height":407,"spotPs":2,"timeCs":3,"imgUrl":[{"img":"https://img.b-ke.cn/52/uploads/2023/04/08/202304081415545166.png","url":null}]}},{"title":"会员信息","name":"myBalance","hide":1,"styles":{"marginTop":0,"marginBottom":10,"tCircle":10,"bCircle":10,"marginLR":15,"type":2,"colorBg":"#fff","zcTxtColor":"#333","zcNumColor":"#333","position":1,"yeMsg":"充值支付更方便","jfMsg":"300积分兑咖啡","yhqMsg":"不定期发放","nLogin":{"cWord":"欢迎光临，请登录","cWordColor":"#333","cTit":"成为会员，享受更多会员权益","cTitColor":"#333","cBtn":"立即登录","cBtnColor":"#fff","cBtnBgColor":"#333"},"zCListO":{"text":null,"name":"会员信息","open":2,"value":1,"img":null},"zCList":[{"text":null,"name":"余额","open":1,"value":1,"img":null},{"text":null,"name":"积分","open":1,"value":2,"img":null},{"text":null,"name":"优惠券","open":1,"value":3,"img":null}],"signed":{"img":null,"url":null},"style3":{"word":"我的特权","wordColor":"#C48611","wordMsg":"您有0项待使用特权，开启提醒不错过","wordMsgColor":"#333","urlLink":{"img":null,"url":null}},"style5":{"imgUrl":[{"img":null,"url":null},{"img":null,"url":null}]},"style8":{"cBg":null,"color":"#f00"},"height":135}},{"title":"热区","name":"hot","hide":1,"styles":{"marginTop":10,"marginBottom":10,"tCircle":10,"bCircle":10,"marginLR":10,"img":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/f542c93e8cb66d97cec2df4c5ae312dd.webp","divStyles":[{"width":"394","height":"347","sX":"0","sY":"0","status":true,"url":"{\"params\":\"platform\",\"name\":{\"name\":\"点餐\",\"id\":\"goods\"},\"category\":\"基础页面\"}","name":"点餐"},{"width":"322","height":"343","sX":"419","sY":"1","status":true,"url":"{\"params\":\"platform\",\"name\":{\"name\":\"点餐\",\"id\":\"goods\"},\"category\":\"基础页面\"}","name":"点餐"}]}},{"title":"图片","name":"pictures","hide":1,"styles":{"marginTop":10,"marginBottom":10,"tCircle":10,"bCircle":10,"marginLR":10,"paddingTop":10,"height":132,"imgUrl":[{"img":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/c95a5b350dbf9c770a62c9845a6a3e38.webp","url":null}]}}],"pageSetting":{"title":"页面设置","name":"nameless","styles":{"modulePage":"1","colorIcon":"#f9f9f9","colorBg":"#fff","moduleTitle":"1","title":"列表标题","navTitle":"奶茶go","navMsg":"奶茶模板","colorWord":"#333","navOpen":"1","navTitColorType":"1","navTitWhite":"#fff","navTitBlack":"#000","navColorBg":"#fff","msgOpen":"2","msgOpenType":"1","msgTxt":"点击`添加至我的小程序`下次访问更方便","img":null,"adOpen":"2","navLeft":"2","navLeftImg":null,"imgUrl":[{"img":null,"url":null}]}}}',
                'created_at' => '2023-04-08 14:14:18',
                'updated_at' => '2023-04-08 16:04:59',
                'appType' => 1,
                'channel' => 1,
                'state' => 1,
                'releaseTime' => '2023-04-08 14:16:04',
                'notes' => '店铺首页模板',
            ),
            1 =>
            array(
                'title' => '个人中心',
                'uniacid' => 52,
                'type' => NULL,
                'data' => '{"list":[{"title":"会员组件","name":"myVip","hide":1,"styles":{"type":1,"colorBg":"#155bd4","imgList":{"img":"https://v3.bkycms.com/storage/52/uploads/2023/03/31/2e00fa17c1f6519acdf9c95f98cafa91.jpeg","url":null},"vipCode":1,"myPro":1,"proList":[{"open":1,"img":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/f047a859ebf42a20778a6a6ff0e92ffc.png","name":"积分","leftWord":"积分","leftColor":"#212121","unit":"个","url":null,"icon":"icon-bonus-s"},{"open":1,"img":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/d98e563f2bfb661d485f81abcabeb36d.png","name":"优惠券","leftWord":"优惠券","leftColor":"#212121","unit":"张","url":null,"icon":"icon-youhuiquan1"},{"open":1,"img":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/114c6553178597ee454f7cd542530ab7.png","name":"余额","leftWord":"余额","leftColor":"#212121","unit":"元","url":null,"icon":"icon-yue1"}],"myOrder":1,"orderList":[{"img":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/6d07a71e8eed7bb42f1b77c786b2b3af.png","leftWord":"我的订单","leftColor":"#212121","url":{"params":"platform","name":{"name":"订单","id":"myOrder"},"category":"基础页面"}}],"tCircle":0,"bCircle":0,"marginTop":0,"marginBottom":0,"marginLR":0,"leftColor":"#212121","rightColor":"#969696","img":null}},{"title":"按钮组","name":"btn","hide":1,"styles":{"line":2,"btnNameShow":1,"num":4,"marginTop":0,"marginBottom":0,"tCircle":0,"bCircle":0,"circleBtn":50,"btnSize":30,"marginLR":0,"colorBg":"#FFFFFF","colorBodyBg":"#FFFFFF","colorWord":"#2c2c2c","btnList":[{"img":"https://v3.bkycms.com/storage/52/uploads/2023/03/31/e607067aad953f6485b388828e84895b.png","word":"我的地址","url":{"params":"platform","name":{"name":"我的地址","id":"myAddress"},"category":"基础页面"},"labelOpen":"2","labelWord":"热门","colorLabelBg":"#F00","colorLabelWord":"#FFFFFF"},{"img":"https://v3.bkycms.com/storage/52/uploads/2023/03/31/f565158d8b6772944eb9e77b8d11c7b3.png","word":"联系客服","url":{"params":"platform","name":{"name":"联系客服","id":"contactCustomer"},"category":"基础页面"},"labelOpen":"2","labelWord":"热门","colorLabelBg":"#F00","colorLabelWord":"#FFFFFF"},{"img":"https://v3.bkycms.com/storage/52/uploads/2023/03/31/60c624aff15192524d14de9c907c57b1.png","word":"联系我们","url":{"params":"platform","name":{"name":"关于我们","id":"aboutUs"},"category":"基础页面"},"labelOpen":"2"},{"img":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/af6b95cdc486799cc5cde36cb7e08dcd.png","word":"协议政策","url":{"params":"platform","name":{"name":"协议政策","id":"conceal"},"category":"基础页面"},"labelOpen":"2"},{"img":"https://v3.bkycms.com/storage/52/uploads/2023/03/31/95620937ad9f41bd2449dca0da4f0313.png","word":"会员码","url":{"params":"platform","name":{"name":"会员码","id":"vipCode"},"category":"基础页面"},"labelOpen":"2"}]}},{"title":"图片","name":"pictures","hide":1,"styles":{"marginTop":11,"marginBottom":10,"tCircle":10,"bCircle":10,"marginLR":10,"paddingTop":10,"height":141,"imgUrl":[{"img":"https://v3.bkycms.com/storage/52/uploads/2023/03/31/942e679db5d00ff7b5aa9de0d0cf5bb9.jpeg","url":null}]}}],"pageSetting":{"title":"页面设置","name":"nameless","styles":{"modulePage":"1","colorIcon":"#f9f9f9","colorBg":"#fff","moduleTitle":"1","title":"列表标题","navTitle":"个人中心","navMsg":"个人中心模板","colorWord":"#333","navOpen":"1","navTitColorType":"1","navTitWhite":"#fff","navTitBlack":"#000","navColorBg":"#fff","msgOpen":"2","msgOpenType":"1","msgTxt":"点击`添加至我的小程序`下次访问更方便","img":null,"adOpen":"2","navLeft":"2","navLeftImg":null,"imgUrl":[{"img":null,"url":null}]}}}',
                'created_at' => '2023-04-08 15:02:46',
                'updated_at' => '2023-04-08 15:09:17',
                'appType' => 2,
                'channel' => 1,
                'state' => 1,
                'releaseTime' => '2023-04-08 15:02:46',
                'notes' => '个人中心模板',
            ),
            2 =>
            array(
                'title' => '配色风格',
                'uniacid' => 52,
                'type' => NULL,
                'data' => '{"activeIndex":5,"color":{"text":"自定义","btnColor":"#007A53","textColor":"#3f3f3f"}}',
                'created_at' => '2023-04-08 15:11:19',
                'updated_at' => '2023-04-08 15:11:19',
                'appType' => 6,
                'channel' => 1,
                'state' => 1,
                'releaseTime' => '2023-04-08 15:11:19',
                'notes' => NULL,
            ),
            3 =>
            array(
                'title' => '全局配置',
                'uniacid' => 52,
                'type' => 'defaultImg',
                'data' => '[{"id":0,"url":"static\/img\/loading.f9836dd4.gif","upUrl":"https:\/\/v3.bkycms.com\/storage\/default\/drag\/0\/loading.gif","txt":null,"type":"loading","navTxt":"\u52a0\u8f7d\u4e2d"},{"id":1,"url":"static\/img\/order.f24fb8dd.png","upUrl":"https:\/\/v3.bkycms.com\/storage\/default\/drag\/0\/order.png","txt":"\u60a8\u6682\u65f6\u8fd8\u6ca1\u6709\u8ba2\u5355\u54e6~","type":"order","navTxt":"\u8ba2\u5355"},{"id":2,"url":"static\/img\/coupon.0c48c881.png","upUrl":"https:\/\/v3.bkycms.com\/storage\/default\/drag\/0\/coupon.png","txt":"\u60a8\u6682\u65f6\u6ca1\u6709\u4f18\u60e0\u5238\u54e6~","type":"coupon","navTxt":"\u4f18\u60e0\u5238"},{"id":3,"url":"static\/img\/address.9bfb542f.png","upUrl":"https:\/\/v3.bkycms.com\/storage\/default\/drag\/0\/address.png","txt":"\u60a8\u6682\u65f6\u8fd8\u6ca1\u6709\u5730\u5740\u54e6~","type":"address","navTxt":"\u5730\u5740"},{"id":4,"url":"static\/img\/good.81aa10e6.png","upUrl":"https:\/\/v3.bkycms.com\/storage\/default\/drag\/0\/good.png","txt":"\u5f53\u524d\u95e8\u5e97\u6682\u65e0\u53ef\u552e\u5546\u54c1","type":"good","navTxt":"\u95e8\u5e97\u65e0\u5546\u54c1"},{"id":5,"url":"static\/img\/storeValue.4a5eef01.png","upUrl":"https:\/\/v3.bkycms.com\/storage\/default\/drag\/0\/storeValue.png","txt":"\u5f53\u524d\u8fd8\u6ca1\u6709\u50a8\u503c\u54e6~","type":"storeValue","navTxt":"\u50a8\u503c"},{"id":6,"url":"static\/img\/giftCard.bb90dfb0.png","upUrl":"https:\/\/v3.bkycms.com\/storage\/default\/drag\/0\/giftCard.png","txt":"\u5f53\u524d\u8fd8\u6ca1\u6709\u793c\u54c1\u5361\u54e6~","type":"giftCard","navTxt":"\u793c\u54c1\u5361"},{"id":7,"url":"static\/img\/integral.365e776d.png","upUrl":"https:\/\/v3.bkycms.com\/storage\/default\/drag\/0\/integral.png","txt":"\u5f53\u524d\u8fd8\u6ca1\u6709\u79ef\u5206\u54e6~","type":"integral","navTxt":"\u79ef\u5206"}]',
                'created_at' => '2023-04-08 16:25:51',
                'updated_at' => '2023-04-08 16:25:51',
                'appType' => 7,
                'channel' => 1,
                'state' => 1,
                'releaseTime' => '2023-04-08 16:25:51',
                'notes' => NULL,
            ),
            5 =>
            array(
                'title' => '全局配置',
                'uniacid' => 52,
                'type' => 'firing',
                'data' => '{"autoCloseTime":3,"state":2,"numType":1}',
                'created_at' => '2023-04-08 16:27:54',
                'updated_at' => '2023-04-08 16:27:54',
                'appType' => 7,
                'channel' => 1,
                'state' => 1,
                'releaseTime' => '2023-04-08 16:27:54',
                'notes' => NULL,
            ),
            6 =>
            array(
                'title' => '全局配置',
                'uniacid' => 52,
                'type' => 'copywriting',
                'data' => '{"autoCloseTime":0,"state":0,"numType":1}',
                'created_at' => '2023-04-08 16:27:58',
                'updated_at' => '2023-04-08 16:27:58',
                'appType' => 7,
                'channel' => 1,
                'state' => 1,
                'releaseTime' => '2023-04-08 16:27:58',
                'notes' => NULL,
            ),
            7 =>
            array(
                'title' => '全局配置',
                'uniacid' => 52,
                'type' => 'loadingImg',
                'data' => '{"animationType":0,"ci":1,"imgUrl":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/sty2.gif","imgList":["https://v3.bkycms.com/storage/52/uploads/2023/04/08/sty1.gif","https://v3.bkycms.com/storage/52/uploads/2023/04/08/sty2.gif"]}',
                'created_at' => '2023-04-08 16:28:15',
                'updated_at' => '2023-04-08 16:28:15',
                'appType' => 7,
                'channel' => 1,
                'state' => 1,
                'releaseTime' => '2023-04-08 16:28:15',
                'notes' => NULL,
            ),
            9 =>
            array(
                'title' => '店铺导航',
                'uniacid' => 52,
                'type' => NULL,
                'data' => '{"type":2,"sv":2,"bg":"#fff","unCheckColor":"#000","checkColor":"#000","list":[{"title":"首页","url":"pages/index/index","iconSelect":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/ecf8210d76c1cff1fafa3a0c346c9c5f.png","icon":"https://img.b-ke.cn/52/uploads/2023/04/08/202304081425553694.png","showLinkTxt":"首页-pages/index/index"},{"title":"点单","url":"pages/index/goods","iconSelect":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/01d008aadb03c6e83b272079b086d5f4.png","icon":"https://img.b-ke.cn/52/uploads/2023/04/08/202304081425559438.png","showLinkTxt":"点单-pages/index/shop"},{"title":"会员","url":"pages/my/user/mr-code","iconSelect":"https://img.b-ke.cn/52/uploads/2023/04/08/202304081425558434.gif","icon":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/62cd1ee9113f16f37812442e3b36f5bb.gif"},{"title":"订单","url":"pages/index/order-index","iconSelect":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/5ff1a4403b963b3717a3f6914fa8416d.png","icon":"https://img.b-ke.cn/52/uploads/2023/04/08/202304081425553617.png","showLinkTxt":"订单-pages/index/order"},{"title":"我的","url":"pages/index/my-index","iconSelect":"https://v3.bkycms.com/storage/52/uploads/2023/04/08/494434ae7a7817a7ff4a59adc458c8c0.png","icon":"https://img.b-ke.cn/52/uploads/2023/04/08/202304081425556959.png","showLinkTxt":"我的-pages/index/my"}]}',
                'created_at' => '2023-04-08 16:32:34',
                'updated_at' => '2023-04-08 16:34:25',
                'appType' => 5,
                'channel' => 1,
                'state' => 1,
                'releaseTime' => '2023-04-08 16:34:25',
                'notes' => NULL,
            ),
        );
        $data = collect($data)->map(function ($item) use ($uniacid, $storeId) {
            $item['data'] = json_encode(json_decode($item['data'], true), 320);

            $item['data'] = str_replace('https://v3.bkycms.com/storage/52/uploads/2023/03/31/', Storage::disk('public')->url('default/drag/0/'), $item['data']);
            $item['data'] = str_replace('https://v3.bkycms.com/storage/default/drag/0/', Storage::disk('public')->url('default/drag/0/'), $item['data']);
            $item['data'] = str_replace('https://v3.bkycms.com/storage/52/uploads/2023/04/08', Storage::disk('public')->url('default/drag/0/'), $item['data']);
            $item['data'] = str_replace('https://img.b-ke.cn/52/uploads/2023/04/08/', Storage::disk('public')->url('default/drag/0/'), $item['data']);
            return collect($item)
                ->put("uniacid", $uniacid)
                ->put("storeId", $storeId)
                ->put('created_at', date("Y-m-d H:i:s", time()))
                ->put('updated_at', date("Y-m-d H:i:s", time()));
        })->toArray();
        Drag::insert($data);
    }

    public static function applyVoiceSeed($uniacid = 0, $storeId = 0)
    {
        try {
            $data = array(
                0 =>
                array(
                    'sort' => 1,
                    'uniacid' => 0,
                    'type' => 'newOrder',
                    'num' => '2',
                    'voicType' => 0,
                    'baseUrl' => '/storage/default/voice/newOrder.mp3',
                    'url' => 'https://v3.bkycms.com/storage/default/voice/newOrder.mp3',
                    'created_at' => '2023-09-07 15:40:00',
                    'updated_at' => '2023-09-09 10:28:49',
                    'name' => '小程序下单提醒',
                ),
                1 =>
                array(
                    'sort' => 3,
                    'uniacid' => 0,
                    'type' => 'inStoreNewOrder',
                    'num' => '4',
                    'voicType' => 0,
                    'baseUrl' => '/storage/default/voice/inStoreNewOrder.mp3',
                    'url' => '',
                    'created_at' => '2023-09-07 15:40:00',
                    'updated_at' => '2023-09-09 09:55:17',
                    'name' => '桌台新订单提醒',
                ),
                2 =>
                array(
                    'sort' => 2,
                    'uniacid' => 0,
                    'type' => 'receive',
                    'num' => '2',
                    'voicType' => 0,
                    'baseUrl' => '/storage/default/voice/receive.mp3',
                    'url' => NULL,
                    'created_at' => '2023-09-07 15:40:00',
                    'updated_at' => '2023-09-08 13:39:41',
                    'name' => '新订单提醒',
                ),
                3 =>
                array(
                    'sort' => 7,
                    'uniacid' => 0,
                    'type' => 'refundApply',
                    'num' => '2',
                    'voicType' => 0,
                    'baseUrl' => '/storage/default/voice/refundApply.mp3',
                    'url' => '',
                    'created_at' => '2023-09-07 15:40:00',
                    'updated_at' => '2023-09-09 10:54:06',
                    'name' => '订单申请退款（外送/自取）',
                ),
                4 =>
                array(
                    'sort' => 4,
                    'uniacid' => 0,
                    'type' => 'complete',
                    'num' => '1',
                    'voicType' => 0,
                    'baseUrl' => '/storage/default/voice/complete.mp3',
                    'url' => NULL,
                    'created_at' => '2023-09-07 15:40:00',
                    'updated_at' => '2023-09-07 15:40:00',
                    'name' => '桌台结账提醒（店内）',
                ),
                5 =>
                array(
                    'sort' => 5,
                    'uniacid' => 0,
                    'type' => 'deliveryAbnormal',
                    'num' => '1',
                    'voicType' => 0,
                    'baseUrl' => '/storage/default/voice/deliveryAbnormal.mp3',
                    'url' => NULL,
                    'created_at' => '2023-09-07 15:40:00',
                    'updated_at' => '2023-09-07 15:40:00',
                    'name' => '订单配送异常',
                ),
                6 =>
                array(
                    'sort' => 6,
                    'uniacid' => 0,
                    'type' => 'appointment',
                    'num' => '1',
                    'voicType' => 0,
                    'baseUrl' => '/storage/default/voice/appointment.mp3',
                    'url' => '',
                    'created_at' => '2023-09-07 15:40:00',
                    'updated_at' => '2023-09-09 09:55:22',
                    'name' => '预约订单提醒（外送/自取）',
                ),
            );
            $data = collect($data)->map(function ($item) use ($uniacid, $storeId) {
                return collect($item)->put('uniacid', $uniacid)
                    ->put('storeId', $storeId)
                    ->put('created_at', date("Y-m-d H:i:s", time()))
                    ->put('updated_at', date("Y-m-d H:i:s", time()));
            })->toArray();
            VoiceMessage::where('uniacid', $uniacid)
                ->where('storeId', $storeId)
                ->delete();
            VoiceMessage::insert($data);
        } catch (\Exception) {
            return false;
        }
    }

    public static function VipPower($uniacid, $storeId = 0)
    {
        $data = array(
            0 =>
            array(
                'sort' => 1,
                'type' => 'balance',
                'icon' => Storage::disk('public')->url('default/drag/0/') . '/202306161358224168.png',
                'name' => '赠送余额',
                'showName' => '赠送余额',
                'desc' => '赠送余额赠送余额',
                'state' => 1,
                'created_at' => date("Y-m-d H:i:s", time()),
                'updated_at' => date("Y-m-d H:i:s", time()),
                'deleted_at' => NULL,
                'uniacid' => 0,
            ),
            1 =>
            array(
                'sort' => 2,
                'type' => 'integral',
                'icon' => Storage::disk('public')->url('default/drag/0/') . '202306161358223827.png',
                'name' => '赠送积分',
                'showName' => '赠送积分',
                'desc' => '赠送积分赠送积分',
                'state' => 1,
                'created_at' => date("Y-m-d H:i:s", time()),
                'updated_at' => date("Y-m-d H:i:s", time()),
                'deleted_at' => NULL,
                'uniacid' => 0,
            ),
            2 =>
            array(
                'sort' => 2,
                'type' => 'couponGive',
                'icon' => Storage::disk('public')->url('default/drag/0/') . '202306161358228966.png',
                'name' => '赠送优惠券',
                'showName' => '赠送优惠券',
                'desc' => '赠送优惠券赠送优惠券',
                'state' => 1,
                'created_at' => date("Y-m-d H:i:s", time()),
                'updated_at' => date("Y-m-d H:i:s", time()),
                'deleted_at' => NULL,
                'uniacid' => 0,
            ),
            3 =>
            array(
                'sort' => 4,
                'type' => 'discount',
                'icon' => Storage::disk('public')->url('default/drag/0/') . '202306161358229073.png',
                'name' => '商品折扣',
                'showName' => '商品折扣',
                'desc' => '商品折扣商品折扣',
                'state' => 1,
                'created_at' => date("Y-m-d H:i:s", time()),
                'updated_at' => date("Y-m-d H:i:s", time()),
                'deleted_at' => NULL,
                'uniacid' => 0,
            ),
            4 =>
            array(
                'sort' => 5,
                'type' => 'integralMultiplier',
                'icon' => Storage::disk('public')->url('default/drag/0/') . '202306161358224903.png',
                'name' => '积分倍率',
                'showName' => '积分倍率',
                'desc' => '积分倍率积分倍率',
                'state' => 1,
                'created_at' => date("Y-m-d H:i:s", time()),
                'updated_at' => date("Y-m-d H:i:s", time()),
                'deleted_at' => NULL,
                'uniacid' => 0,
            ),
            5 =>
            array(
                'sort' => 6,
                'type' => 'freeMailSwitch',
                'icon' => Storage::disk('public')->url('default/drag/0/') . '202306161359348552.png',
                'name' => '免配送费',
                'showName' => '免配送费',
                'desc' => '免配送费免配送费',
                'state' => 1,
                'created_at' => date("Y-m-d H:i:s", time()),
                'updated_at' => date("Y-m-d H:i:s", time()),
                'deleted_at' => NULL,
                'uniacid' => 0,
            ),
        );
        $data = collect($data)->map(function ($item) use ($uniacid, $storeId) {
            return collect($item)->put('uniacid', $uniacid)
                ->put('storeId', $storeId)
                ->put('created_at', date("Y-m-d H:i:s", time()))
                ->put('updated_at', date("Y-m-d H:i:s", time()));
        })->toArray();
        \DB::table('member_vip_power')->insert($data);
    }
}
