<?php

namespace App\Services\Delivery;

use App\Models\Config;
use app\models\common\SanSong;
use App\Models\Delivery\Uupt;
use App\Models\Delivery\Channel;
use App\Models\Delivery\Store;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\Maiyatian\Application;
use App\Services\ConfigService;
use App\Services\DeliveryService;
use Dada\Base;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Models\Store as Stores;
class UuService
{
    //以下为uu跑腿接口
    public static function getUuptSet($storeId, $uniacid)
    {

        $storeSet = Channel::where('uniacid', $uniacid)
            ->where('storeId', $storeId)
            ->where('type', 8)->first();
        $storeSet= empty($storeSet) ? array() : $storeSet->toArray();
        $storeSet=$storeSet['config'];
        $config['appid'] = $storeSet['uuAppId'] ?: 'ccba8bd4a2d54a2fb6df97e87979f303';
        $config['appKey'] = $storeSet['uuAppKey'] ?: '2815a7a1f8e3405d81fd6263683ec4e7';
        $config['openid'] = $storeSet['uuOpenId'] ?: '910a0dfd12bb4bc0acec147bcb1ae246';
        //$config['domain'] = "http://openapi.test.uupt.com/v2_0/";
        $config['domain'] = "https://openapi.uupt.com/v2_0/";

        return $config;
    }

    //获取订单价格
    static function getOrderPrice($order, $config) {

        header("Content-type: text/html; charset=utf-8");
        $guid = str_replace('-', '', Uupt::guid());
        // $config = self::getUuptSet($order['storeId']);
        $store=Stores::find($order['storeId'])->toArray();

        $city = $store['regionFormat'][1];
        $sellerZb = coordinateSwitchf($store['lat'], $store['lng']);
        $orderZb = coordinateSwitchf($order['lat'], $order['lng']);
        $url = $config['domain']."getorderprice.ashx";
        $data = [
            'origin_id' => $order['outTradeNo'],
            'from_address' => $store['address'],
            'to_address' => $order['receivedAddress'],
            'city_name' => $city,
            'to_lng' => $orderZb['lng'],
            'to_lat' => $orderZb['lat'],
            'from_lng' => $sellerZb['lng'],
            'from_lat' => $sellerZb['lat'],
            'nonce_str' => strtolower($guid),
            'timestamp' => time(),
            'appid' => $config['appid'],
            'openid' => $config['openid'],
            'send_type' => 0,
        ];
        if($store['zoneId']){
            $county=(new \yii\db\Query())
                ->from('{{%ybwm_core_district}}')
                ->where('id=:id',[':id'=>$store['zoneId']])->one();
            $data['county_name']=$county['name'];
        }

        ksort($data);
        $data['sign'] =  Uupt::sign($data, $config['appKey']);

        $res = Uupt::request_post($url, $data);

        return $res;
    }

    static function  addUuptOrder($order){

        $config = self::getUuptSet($order['storeId'], $order['uniacid']);

        $priceInfo = json_decode(self::getOrderPrice($order, $config), true);
        if (!isset($priceInfo['price_token'])) {
            echo json_encode(['code'=>2,'msg'=>$priceInfo['return_msg']]);die;
            return $priceInfo['return_msg'];
        }
        header("Content-type: text/html; charset=utf-8");
        $guid = str_replace('-', '', Uupt::guid());
        $url = $config['domain']."addorder.ashx";
        $domain_url= 'https://' .  Request()->header('HTTP_HOST');
        $callBack=$domain_url.'/channel/notify/uu';
        $data = [
            'price_token' => $priceInfo['price_token'],
            'order_price' => $priceInfo['total_money'],
            'balance_paymoney' => $priceInfo['need_paymoney'],
            'receiver' => $order['receivedName'],
            'receiver_phone' => $order['receivedTel'],
            'note' => $order['takeNo'],
            'callback_url' =>$callBack,
            'push_type' => 0,
            'special_type' => 0,
            'callme_withtake' => 0,
            'nonce_str' => strtolower($guid),
            'timestamp' => time(),
            'appid' => $config['appid'],
            'openid' => $config['openid'],
        ];
        //var_dump($data);die;
        ksort($data);
        $data['sign'] = Uupt::sign($data, $config['appKey']);
        $res = Uupt::request_post($url, $data);
        $result=json_decode($res,true);
        if($result['return_code']!=='ok'){
            echo json_encode(['code'=>$result['return_code'],'msg'=>$result['return_msg']]);die;
        }
        $result['otherFee']=$priceInfo['need_paymoney'];
        return $result;
    }

//取消订单
    static function cancelUuptOrder($order){
        header("Content-type: text/html; charset=utf-8");
        $guid = str_replace('-', '', Uupt::guid());
        $config = self::getUuptSet($order['storeId'],$order['uniacid']);
        $url = $config['domain']."cancelorder.ashx";
        $data = [
            'origin_id' => $order['outTradeNo'],
            'reason' =>'用户取消订单',
            'nonce_str' => strtolower($guid),
            'timestamp' => time(),
            'appid' =>$config['appid'],
            'openid' => $config['openid'],
        ];

        ksort($data);
        $data['sign'] = Uupt::sign($data,  $config['appKey']);
        //var_dump($data);die;
        $res = Uupt::request_post($url, $data);
        $result= json_decode($res, true);
        return $result;
    }

//uu获取账户余额
    static function UuptUserAccount($storeId,$uniacid){
        header("Content-type: text/html; charset=utf-8");
        $guid = str_replace('-', '', Uupt::guid());
        $config = self::getUuptSet($storeId,$uniacid);
        $url = $config['domain']."getbalancedetail.ashx";
        $data = [
            'nonce_str' => strtolower($guid),
            'timestamp' => time(),
            'appid' =>$config['appid'],
            'openid' => $config['openid'],
        ];

        ksort($data);
        $data['sign'] = Uupt::sign($data,  $config['appKey']);
        //var_dump($data);die;
        $res = Uupt::request_post($url, $data);
        if(json_decode($res, true)['return_code']=='ok'){
            return json_decode($res, true)['AccountMoney'];
        }
    }

}
