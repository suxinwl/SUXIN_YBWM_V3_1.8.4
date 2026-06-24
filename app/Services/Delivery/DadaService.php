<?php

namespace App\Services\Delivery;

use App\Models\Delivery\Channel;
use App\Models\Delivery\Store;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\Maiyatian\Application;
use App\Services\ConfigService;
use App\Services\DeliveryService;
use Dada\Base;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DadaService
{
    public static function getDadaSet($storeId, $uniacid)
    {
        $storeSet = Channel::where('uniacid', $uniacid)
            ->where('storeId', $storeId)
            ->where('type', 9)->first();

        $storeSet= empty($storeSet) ? array() : $storeSet->toArray();
        $storeSet=$storeSet['config'];
        $config['app_key'] = $storeSet['dadaAppKey']?:'dadad6115877aaf4994';
        $config['app_secret'] = $storeSet['dadaAppSecret']?:'515d79a0efcd041ce7c1e852258f7b82';
        $config['source_id'] = $storeSet['dadaShopId']?:'73753';
        $config['shop_no'] = $storeSet['dadaShopNo']?:'11047059';
        //$config['domain'] = "https://newopen.qa.imdada.cn";
        $config['domain'] = "https://newopen.imdada.cn";
        return $config;
    }

    public static function getSign($orderInfo,$body)
    {
        $config = self::getDadaSet($orderInfo['storeId'], $orderInfo['uniacid']);
        $app_key=$config['app_key'];
        $source_id=$config['source_id'];
        $app_secret=$config['app_secret'];
        $array = array(
            'format' => 'json',
            'app_key' => $app_key,
            'v' => '1',
            'source_id ' => $source_id,
        );
        if ($body) {
            $array['body'] = json_encode($body);
        }
        ksort($array, 2);
        $str = '';
        foreach ($array as $k => $v) {
            $str .= $k . $v;
        }
        $str .= $app_secret;
        $sign = strtoupper(Md5($str));
        $array['signature']=$sign;
        return $array;
    }
    //
    public static function addDataOrder($orderInfo, $type= null)
    {
        $config = self::getDadaSet($orderInfo['storeId'], $orderInfo['uniacid']);
        $url=$config['domain'].'/api/order/addOrder';
        $config = self::getDadaSet($orderInfo['storeId'], $orderInfo['uniacid']);

        $domain_url= 'https://' .  Request()->header('HTTP_HOST');
        $callBack=$domain_url.'/channel/notify/dada';

        $data = array(
            'shop_no' => $config['shop_no'], //门店编号
            'origin_id' => $orderInfo['orderSn'], //订单id
            'tips' => 0, //小费
            'info' => $orderInfo['outTradeNo'], //备注
            'cargo_weight' => 1,
            'cargo_price' => $orderInfo['money'],
            'is_prepay' => 0,
            'expected_fetch_time' => time() + 600,
            'receiver_name' => $orderInfo['receivedName'],
            'receiver_address' => $orderInfo['receivedAddress'],
            'receiver_phone' => $orderInfo['receivedTel'],
            'receiver_lat' => $orderInfo['lat'],
            'receiver_lng' => $orderInfo['lng'],
            'callback' => $callBack,
        );
        $row=self::getSign($orderInfo,$data);
        $result =httpRequest($url,$row);
        var_dump($result);die;
        /* if (is_array($result)) {
             return 'success';
         }*/
        return $result;
    }

    //取消订单
    static function cancelDataOrder($orderInfo)
    {
        $config = self::getDadaSet($orderInfo['storeId'], $orderInfo['uniacid']);
        $dada = new Base($config['app_key'], $config['app_secret']);
        $dada->setSourceId($config['source_id']);
        $order = $dada->order;
        $data['order_id'] = $orderInfo['outTradeNo'];
        $data['cancel_reason_id'] = 36;
        try{
            $result = $order->cancel($data)->getResult();
            if (is_array($result)) {
                return true;
            }
        }catch (\Exception $e){
            throw new BadRequestException($e->getMessage());
        }
    }


    //查询账户额度
    static function dadaUserAccount($storeId,$uniacid)
    {
        $config = self::getDadaSet($storeId, $uniacid);
        $dada = new Base($config['app_key'], $config['app_secret']);
        $dada->setSourceId($config['source_id']);
        $balance = $dada->balance;
        $data['category'] = 3;
        $result = $balance->query($data)->getResult();
        if (is_array($result)) {
            return $result['deliverBalance'];
        }

    }
}
