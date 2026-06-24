<?php

namespace App\Services\Delivery;

use App\Models\Delivery\Channel;
use App\Models\Delivery\Store;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\Maiyatian\Application;
use App\Services\ConfigService;
use App\Services\DeliveryService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MaiyatianService
{

    public static function app()
    {
        $config = ConfigService::getSystemSet('deliverySetting');
        $config = [
            'mch_id'             => '',
            'deliveryType' => 1,
            'appkey' =>     $config->AppKey,                                                        //"550586",
            // v3 API 秘钥
            'secretKey' =>      $config->AppSecret,                                // '5ed573739c87a5cf66a5279c4fc1421c',
            'http' => [
                'throw'  => false, // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
                'base_uri' => 'https://api-open.m.maiyatian.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ]
        ];
        return new Application($config);
    }

    public static function storeApp($storeId, $uniacid)
    {
        $channelConfig = self::getConfig($storeId, $uniacid);
        $config = ConfigService::getSystemSet('deliverySetting');
        $config = [
            'mch_id'             => $channelConfig->channelId,
            'deliveryType' => $channelConfig->storeId == 0 ? 1 : 2,
            'appkey' => $config->AppKey,
            // v3 API 秘钥
            'secretKey' => $config->AppSecret,
            'http' => [
                'throw'  => false, // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
                'base_uri' => 'https://api-open.m.maiyatian.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ]
        ];
        return new Application($config);
    }
    //返回城市列表

    public static function getConfig($storeId, $uniacid)
    {
        $config = Store::where('storeId', $storeId)->first();
        $storeId = $config->deliveryType == 1 ? 0 : $storeId;
        $model = Channel::where(function ($q) use ($storeId, $uniacid) {
            return $q->where('storeId', $storeId)->where('uniacid', $uniacid);
        })->where('type', 1)->first();
        if (empty($model)) {
            throw  new BadRequestHttpException('当前门店或者店铺没有麦芽田授权');
        }
        return $model;
    }



    //创建订单
    public  static function createOrder($order)
    {
        $shopRes = (new \yii\db\Query())
            ->from('{{%ybwm_store}}')
            ->where('id=:id', [':id' => $order['storeId']])
            ->one();
        $url = self::interfaceAddress() . '/channel/order/add/';
        $redisName = 'maiyatianOrder' . date('Ymd');
        $number = Yii::$app->redis->get($redisName);
        if ($number) {
            $number = $number + 1;
        } else {
            $number = 1;
            Yii::$app->redis->set($redisName, $number);
        }
        $shop_id = $shopRes['id'];
        //$domain_url = preg_replace("(^https?://)", "", Yii::$app->request->hostInfo);
        //$domain_url=str_replace('.','',$domain_url);
        // $domain_url=str_replace('-','',$domain_url);
        // $shop_id=$domain_url.'_'.$shop_id;
        $params = array(
            'origin_id' => $order['id'], //你方订单号
            'order_sn' => $number, //订单流水号 【必须是0~99999以内的数字】
            'shop_id' => $shop_id, //你方门店ID
            'shop_name' => $shopRes['name'], //你方门店名称
            'is_subscribe' => 0, //是否预约单
            'subscribe_time' => 0, //期望发单时间(秒时间戳)
            'city' => $shopRes['cityCode'], //城市编码
            'sender_longitude' => $shopRes['lng'], //发件人经度
            'sender_latitude' => $shopRes['lat'], //发件人纬度
            'sender_address' => $shopRes['address'], //发件人地址
            'sender_phone' => $shopRes['storeTel'], //发件人手机号
            'receiver_longitude' => $order['lng'], //收件人经度
            'receiver_latitude' => $order['lat'], //收件人纬度
            'receiver_address' => $order['receivedAddress'], //收件人地址
            'receiver_address_detail' => $order['receivedAddress'], //收件人详细地址
            'receiver_name' => $order['receivedName'], //收件人姓名
            'receiver_phone' => $order['receivedTel'], //收件人手机号 【如果是只有虚拟号码，虚拟号格式（手机号_分机号码）例如：13700000000_1111】
            'remark' => '', //	备注
            'order_source' => 'other',
            'order_source_no' => $number,
            'goods_category' => 1,
            'map_type' => 1,
            'goods_value' => 300,
            'goods_weight' => 300
        );
        $data = self::requestParams($url, $params);
        return $data;
    }
    //手动发单
    public static function receipt($origin_id, $logistics, $tip = '0')
    {
        $url = self::interfaceAddress() . '/order/send';
        $params = array(
            'origin_id' => $origin_id, //你方订单ID
            'logistics' => $logistics, //配送方，多个用,号分割
            'tip' => $tip, //小费 单位: 元
            'expect_receive_time' => 0,
            'remark' => ''
        );
        $data = self::requestParams($url, $params);
        //file_put_contents('1.txt',$data);
        return $data;
    }
    //订单详情
    public static function orderDetail($origin_id)
    {
        $url = self::interfaceAddress() . '/order/detail';
        $params = array(
            'origin_id' => $origin_id, //你方订单ID
        );
        $data = self::requestParams($url, $params);
        return $data;
    }
    //骑手位置
    public static function riderPosition($origin_id)
    {
        $url = self::interfaceAddress() . '/delivery/trail';
        $params = array(
            'origin_id' => $origin_id, //你方订单ID
        );
        $data = self::requestParams($url, $params);
        return $data;
    }
    //取消单次配送
    public static function orderCancel($origin_id, $delivery_no)
    {
        $url = self::interfaceAddress() . '/delivery/cancel';
        $params = array(
            'origin_id' => $origin_id, //你方订单ID
            'delivery_no' => $delivery_no //我方配送no
        );
        $data = self::requestParams($url, $params);
        return $data;
    }
    //取消所有配送
    public static function orderAllCancel($origin_id)
    {
        $url = self::interfaceAddress() . '/order/cancelDelivery';
        $params = array(
            'origin_id' => $origin_id, //你方订单ID
        );
        $data = self::requestParams($url, $params);
        return $data;
    }
    //配送轨迹
    public static function distributionTrack($origin_id)
    {
        $url = self::interfaceAddress() . '/delivery/gets';
        $params = array(
            'origin_id' => $origin_id, //你方订单ID
        );
        $data = self::requestParams($url, $params);
        return $data;
    }
    //配送状态日志
    public static function deliveryLog($origin_id)
    {
        $url = self::interfaceAddress() . '/delivery/logs';
        $params = array(
            'origin_id' => $origin_id, //你方订单ID
        );
        $data = self::requestParams($url, $params);
        return $data;
    }

    //店铺设置管理
    public function storeSet($shop_id)
    {
        // $domain_url = preg_replace("(^https?://)", "", Yii::$app->request->hostInfo);
        //$domain_url=str_replace('.','',$domain_url);
        // $domain_url=str_replace('-','',$domain_url);
        //$shop_id=$domain_url.'_'.$shop_id;
        $params = ['shop_id' => $shop_id];
        $maiyaData = Config::getSystemSet('maiyaConfig', 0);
        $app_key = $maiyaData['AppKey'];
        $app_secret = $maiyaData['AppSecret'];
        $array = array(
            'app_key' => $app_key,
            'params' => json_encode($params),
            'timestamp' => time(),
            'version' => '1',
        );
        if ($params) {
            $array['params'] = json_encode($params);
        }
        ksort($array, 2);
        $str = $app_secret;
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $str .= $k . json_encode($v);
            } else {
                $str .= $k . $v;
            }
        }
        $str .= $app_secret;
        $sign = strtoupper(Md5($str));
        $app_key = $app_key;
        $url = 'https://m.maiyatian.com/router?route_module=setting&app_key=' . $app_key . '&shop_id=' . $shop_id . '&sign=' . $sign;
        return $url;
    }
}
