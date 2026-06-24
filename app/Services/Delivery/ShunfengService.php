<?php
namespace App\Services\Delivery;
use app\models\common\Config;
use app\models\common\SanSong;
use App\Models\Delivery\Channel;
use App\Models\Delivery\Store;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\Maiyatian\Application;
use App\Services\ConfigService;
use App\Services\DeliveryService;
use Dada\Base;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ShunfengService
{
    //以下为顺丰同城配送
    public static function getShunfengSet($storeId, $uniacid)
    {
        $storeSet = Channel::where('uniacid', $uniacid)
            ->where('storeId', $storeId)
            ->where('type', 6)->first();

        $storeSet= empty($storeSet) ? array() : $storeSet->toArray();
        $storeSet=$storeSet['config'];
        //var_dump($storeSet['sfStoreId']);die;
        $config['dev_key'] = $storeSet['sfAppKey']?:'2d48339a70caa1e2f70492e3f9708be5';
        $config['dev_id'] = $storeSet['sfAppId']?:'1565985728';
        $config['shop_id'] = $storeSet['sfStoreId']?:'3243279847393';
        /*  $config['dev_key'] = $storeSet['sfAppId'];
          $config['dev_id'] = $storeSet['sfAppKey'];
          $config['shop_id'] = $storeSet['sfShopId'];*/
        $config['domain'] = "https://commit-openic.sf-express.com";
        $config['product_type'] = $storeSet['product_type']?:1;
        return $config;
    }

    static function sftcPost($data, $url, $dev_key)
    {
        $jsonData = json_encode($data);
        $signChar = $jsonData . "&{$data['dev_id']}&{$dev_key}";
        $sign = base64_encode(MD5($signChar));
        $url = "{$url}?sign={$sign}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    //顺丰同城下单
    static function sftcAddOrder($order)
    {
        $config = self::getShunfengSet($order['storeId'], $order['uniacid']);
        $post_data = [
            'dev_id' => $config['dev_key'],
            'shop_id' => $config['shop_id'],
            'order_source' => 'mc',
            'shop_order_id' => $order['outTradeNo'],
            'order_sequence' => $order['takeNo'] ?: 1,
            'lbs_type' => 2,
            //'shop_phone' => 66025198,
            'pay_type' => 1,
            'pay_method' => 10,
            'receive_user_money' => 0,
            'order_time' => $order['createdAt'],
            'is_appoint' => 0,
            'push_time' => time(),
            //'expect_time' => $time + 500,
            //'expect_pick_time' => $time + 5000,
            'remark' => $order['userNote'],
            'version' => 17,
        ];
        $receive = [
            'user_name' => $order['receivedName'],
            'user_phone' => $order['receivedTel'],
            'user_lng' => $order['lng'],
            'user_lat' => $order['lat'],
            'user_address' => $order['receivedAddress'],
        ];
        $order_detail = [
            'total_price' => $order['money'] * 100,
            'product_type' => $config['product_type']?:1, //测试店铺请填写1，否则会造成【没有匹配的计价规则或计价规则已失效】
            //'user_money' => 50,
            //'shop_money' => 50,
            'weight_gram' => 1000,
            //'volume_litre' => 5,
            'product_num' => $order['num'],
            'product_type_num' => $order['num'],
            'delivery_money' => $order['deliveryMoney'] * 100,
        ];


        $order_detail['product_detail'] =  ['product_name'=>$order['product_detail'],'product_num'=>1];
        $post_data['order_detail'] = $order_detail;
        $post_data['receive'] = $receive;
        $addOrder = self::sftcPost($post_data, $config['domain'] . '/open/api/external/createorder', $config['dev_id']);
        $result = json_decode($addOrder, true);
        return $result;
        /*  if ($result['error_code'] == 0) {
              return 'success';
          } else {
              return $result['error_msg'];
          }*/
    }

    //顺丰取消订单
    static function cancelShunfengOrder($uniacid,$storeId,$order)
    {
        $config = self::getShunfengSet($storeId, $uniacid);
        $post_data['dev_id'] = $config['dev_id'];
        $post_data['order_id'] = $order;
        $post_data['order_type'] = 2;
        $post_data['shop_id'] = $config['shop_id'];
        $post_data['push_time'] = time();
        $addOrder = self::sftcPost($post_data, $config['domain'] . '/open/api/external/cancelorder', $config['dev_key']);
        $result = json_decode($addOrder, true);
        return $result;
    }

    //顺丰H5轨迹
    static function riderView($order)
    {
        $config = self::getShunfengSet($order['storeId'], $order['uniacid']);
        $post_data['dev_id'] = $config['dev_id'];
        $post_data['order_id'] = $order['outTradeNo'];
        $post_data['order_type'] = 2;
        $post_data['shop_id'] = $config['shop_id'];
        $post_data['push_time'] = time();
        $addOrder = self::sftcPost($post_data, $config['domain'] . '/open/api/external/riderviewv2', $config['dev_key']);
        $result = json_decode($addOrder, true);
        if ($result['error_code'] == 0) {
            return $result['result'];
        } else {
            return $result['error_msg'];
        }
    }
}
