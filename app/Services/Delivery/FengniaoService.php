<?php

namespace App\Services\Delivery;

use app\models\common\Config;
use app\models\common\Fengniao;
use app\models\common\SanSong;
use app\models\common\Uupt;
use App\Models\Delivery\Channel;
use App\Models\Delivery\Store;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\Maiyatian\Application;
use App\Services\ConfigService;
use App\Services\DeliveryService;
use Dada\Base;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FengniaoService
{
    //蜂鸟下单
    static function addFengniaoOrder($order) {
        $storeSet = Channel::where('uniacid', $order['uniacid'])
            ->where('storeId', $order['storeId'])
            ->where('type', 7)->first();
        $url='https://open-anubis.ele.me/anubis-webapi/v3/invoke/createOrder';
        $items_json = [];
        $goods = DB::table('order_goods')
            ->where('orderId=:orderId and item=1', [':orderId' => $order['id']])->all();
        $goods_total_amount_cent=0;
        $goods_count=0;
        foreach ($goods as $key => $value) {
            $name = $value['name'];
            $items_json[$key]['item_name'] = $name;
            $items_json[$key]['item_quantity'] = $value['num'];
            $items_json[$key]['item_amount_cent'] = bcmul($value['money'],100);
            $items_json[$key]['item_actual_amount_cent'] =bcmul($goods[$key]['money'],$goods[$key]['num']);
            $goods_total_amount_cent+=bcmul($goods[$key]['money'],$goods[$key]['num']);
            $goods_count+=$items_json[$key]['num'];
            //$items_json[$key]['agent_purchase_price']=$value['num'];
        }

        $business_data= array(
            'chain_store_id'=>$storeSet['chain_store_id']?:'204933107',
            'partner_order_code' => $order['outTradeNo'], // 第三方订单号, 需唯一
            'order_type' => 1,
            'position_source' => 1,
            'receiver_address' => $order['receivedAddress'],
            'receiver_longitude' => $order['lng'],
            'receiver_latitude' => $order['lat'],
            'serial_number'=>$order['takeNo'],
            'goods_total_amount_cent'=>bcmul($goods_total_amount_cent,100),
            'goods_actual_amount_cent'=>bcmul($goods_total_amount_cent,100),
            'goods_weight'=>0,
            'goods_count'=>$goods_count,
            'goods_item_list'=> $items_json,
            'receiver_name'=>$order['receivedName'],
            'receiver_primary_phone'=> $order['receivedTel']
        );
        $result=Fengniao::checkPost($order,$business_data,$url);
        return $result;
    }

    //蜂鸟查询订单可退款状态
    static function selectFengniaoOrder($order) {
        $url='https://open-anubis.ele.me/anubis-webapi/v3/invoke/getCancelReasonList';
        $business_data= array(
            'partner_order_code'=>$order['id']
        );
        $result=Fengniao::checkPost($order,$business_data,$url);
        if($result['code']==200){
            echo json_encode(['code'=>$result['code'],'msg'=>$result['msg']]);die;
        }else{
            echo json_encode(['code'=>$result['code'],'msg'=>$result['msg']]);die;
        }


    }


    //蜂鸟查询订单
    static function getOrderDetailFengniaoOrder($order) {
        $url='https://open-anubis.ele.me/anubis-webapi/v3/invoke/getOrderDetail';
        $business_data= array(
            'partner_order_code'=>$order['id']
        );
        $result=Fengniao::checkPost($order,$business_data,$url);
        if($result['code']==200){
            echo json_encode(['code'=>$result['code'],'msg'=>$result['msg'],'list'=>$result['business_data']]);die;
        }else{
            echo json_encode(['code'=>$result['code'],'msg'=>$result['msg']]);die;
        }


    }



    //查询蜂鸟订单可取消原因
    static function CancelFengniaoState($order) {
        $config = self::getFengniaoSet($order['storeId'],$order['uniacid']);
        $appId = $config['app_id'];
        $appKey = $config['secret_key'];
        $url='https://open-anubis.ele.me/anubis-webapi/v3/invoke/getCancelReasonList';
        $business_data= array(
            'partner_order_code' =>$order['outTradeNo'], // 第三方订单号, 需唯一
        );
        $result=Fengniao::checkPost($order,$business_data,$url);
        if($result['code']==200){
            echo json_encode(['code'=>$result['code'],'msg'=>$result['msg']]);die;
        }else{
            echo json_encode(['code'=>$result['code'],'msg'=>$result['msg']]);die;
        }


    }
    public static function getFengniaoSet($storeId, $uniacid)
    {
        $storeSet = Config::getStoreSet('deliveryMode', $storeId, $uniacid);
        $config['app_id'] = $storeSet['fnAppId']?:'2995b9bf-bda7-4022-a29b-8b52f858aad1';
        $config['secret_key'] = $storeSet['fnAppKey']?:'f76e8dca-62d7-4987-aabe-4b1b06f88a39';
        $config['chain_merchant_id'] = $storeSet['fnMerchantId']?:'4103883';
        $config['domain'] = "https://exam-anubis.ele.me";
        $config['chain_store_id']=$storeSet['fnStoreId']?:'204933107';
        //$config['domain'] = "https://open-anubis.ele.me";
        return $config;
    }
    //蜂鸟查询余额接口
    static function getAmountFengniao($order) {
        $config = self::getFengniaoSet($order['storeId'],$order['uniacid']);
        $appId = $config['app_id'];
        $appKey = $config['secret_key'];
        $url='https://open-anubis.ele.me/anubis-webapi/v3/invoke/getAmount';
        $business_data=[];
        $result=Fengniao::checkPost($order,$business_data,$url);
        if($result['code']==200){
            echo json_encode(['code'=>$result['code'],'msg'=>$result['msg']]);die;
        }else{
            echo json_encode(['code'=>$result['code'],'msg'=>$result['msg']]);die;
        }


    }

    //蜂鸟取消订单
    static function CancelFengniaoOrder($order) {
        $config = self::getFengniaoSet($order['storeId'],$order['uniacid']);
        $appId = $config['app_id'];
        $appKey = $config['secret_key'];
        $url='https://open-anubis.ele.me/anubis-webapi/v3/invoke/cancelOrder';
        $business_data= array(
            'order_cancel_code'=>32,
            'order_cancel_role' =>1, // 第三方订单号, 需唯一
            'order_id'=>$order['outTradeNo']
        );
        $result=Fengniao::checkPost($order,$business_data,$url);
        return $result;


    }


}
