<?php

namespace App\Services\Delivery;


use App\Models\Delivery\SanSong;
use App\Models\Delivery\Channel;
use App\Models\Delivery\Store;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\Maiyatian\Application;
use App\Services\ConfigService;
use App\Services\DeliveryService;
use Dada\Base;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Models\Store as Stores;
use App\Models\Wechat\Kernel\Exceptions\BadRequestException;
class ShansongService
{
    //以下闪送接口
    public static function getSansongSet($storeId, $uniacid)
    {
        $storeSet = Channel::where('uniacid', $uniacid)
            ->where('storeId', $storeId)
            ->where('type', 10)->first();

        $storeSet= empty($storeSet) ? array() : $storeSet->toArray();
        $storeSet=$storeSet['config'];
        $config['clientId'] = $storeSet['ssAppId']?:'ssMhLh4l5Ou9Qgh4u';
        $config['appSecrty'] = $storeSet['ssAppKey']?:'JF3YRgdc07k93ZyW9DR1At6XRC7o84UV';
        $config['shopId'] = $storeSet['ssShopId']?:'20000000000000730';
        //$config['domain'] = "http://open.s.bingex.com";//测试域名
        $config['domain'] = "http://open.ishansong.com";//生产域名
        return $config;
    }

    //
    //闪送
    static function computationalCost($order, $store)
    {

//        $order=(new \yii\db\Query())
//            ->from('{{%ybwm_takeout_order}}')
//            ->where('id=:id',[':id'=>$orderId])->one();
//        $store=(new \yii\db\Query())
//            ->from('{{%ybwm_store}}')
//            ->where('id=:id',[':id'=>$order['storeId']])->one();
        $storeCoordinate = coordinateSwitchf($store['lat'], $store['lng']);
        $orderCoordinate = coordinateSwitchf($order['lat'], $order['lng']);

        $request['cityName'] = $store['regionFormat'][1];
        $request['sender']['fromAddress'] = $store['address'] ?: "博彦科技大厦";
        $request['sender']['fromAddressDetail'] = $store['address'] ?: "4层101";
        $request['sender']['fromSenderName'] = $store['name'] ?: '小张';
        $request['sender']['fromMobile'] = $store['mobile'] ?: "13693100496";
        $request['sender']['fromLatitude'] = $storeCoordinate['Latitude'] ?: "40.054759";
        $request['sender']['fromLongitude'] = $storeCoordinate['Longitude'] ?: "116.289086";
        $request['receiverList']['orderNo'] = $order['outTradeNo'] ?: "C1119A000013053981";
        $request['receiverList']['toAddress'] = $order['receivedAddress'] ?: "";
        $request['receiverList']['toAddressDetail'] = $order['description'] ?: "";
        $request['receiverList']['toLatitude'] = $orderCoordinate['Latitude'] ?: "40.004532";
        $request['receiverList']['toLongitude'] = $orderCoordinate['Longitude'] ?: "116.475304";
        $request['receiverList']['toReceiverName'] = $order['receivedName'] ?: "朱家帅";
        $request['receiverList']['toMobile'] = $order['receivedTel'] ?: "13545899179";
        $request['receiverList']['goodType'] = 6;
        $request['receiverList']['weight'] = 1;
        if($order['userNote']) {
            $request['receiverList']['remarks'] = $order['notes'];
        }
        //$request['receiverList']['orderingSourceType'] = 1;
        //$request['receiverList']['orderingSourceNo'] = $order['oId'];
        $request['appointType'] = 0;
        $config = self::getSansongSet($order['storeId'], $order['uniacid']);

        $data['clientId'] = $config['clientId'];
        $data['shopId'] = $config['shopId'];
        $data['timestamp'] = time() * 1000;
        $data['data'] = json_encode($request);
        $appSecrty = $config['appSecrty'];
        $url = $config['domain'] . '/openapi/merchants/v5/orderCalculate';

        $data['sign'] = SanSong::generateSignature($data, $appSecrty);
        $res = SanSong::request_post($url, $data);

        return $res;
    }

    //闪送
    static function sanAddOrder($order)
    {
        $store = Stores::where('uniacid', $order['uniacid'])->find($order['storeId']);

        $store= empty($store) ? array() : $store->toArray();

        $calcResult = self::computationalCost($order,$store);

        $calcRes = json_decode($calcResult, true);

        if ($calcRes['status'] == 200) {
            $config = self::getSansongSet($order['storeId'], $order['uniacid']);
            $data['clientId'] = $config['clientId'];
            $data['shopId'] = $config['shopId'];
            $data['timestamp'] = time() * 1000;
            $appSecrty = $config['appSecrty'];
            $data['data'] = json_encode(['issOrderNo' => $calcRes['data']['orderNumber']]);
            $data['sign'] = SanSong::generateSignature($data, $appSecrty);
            $url = $config['domain'] . '/openapi/merchants/v5/orderPlace';
            $result = SanSong::request_post($url, $data);
            $res = json_decode($result, true);
            //var_dump($res);die;
            return  $res;
            /*            if ($res['status'] == 200) {
                            $params['sanOrder'] = $calcRes['data']['orderNumber'];
                            $params['otherFee'] = $calcRes['data']['totalFeeAfterSave'] / 100;
                            Yii::$app->db->createCommand()->update('{{%ybwm_takeout_order}}', $params, 'id=:id', ['id' => $order['id']])->execute();
                            return 'success';
                        } else {
                            return $res['msg'];
                        }*/
        } else {

            throw new BadRequestException($calcRes['msg']);die;
        }
    }

    //闪送取消订单
    static function cancelSanOrder($order)
    {
        $config = self::getSansongSet($order['storeId'], $order['uniacid']);
        $data['clientId'] = $config['clientId'];
        $data['shopId'] = $config['shopId'];
        $data['timestamp'] = time() * 1000;
        $appSecrty = $config['appSecrty'];
        $data['data'] = json_encode(['issOrderNo' => $order['sanOrder']]);
        $data['sign'] = SanSong::generateSignature($data, $appSecrty);

        $url = $config['domain'] . '/openapi/developer/v5/abortOrder';
        $result = SanSong::request_post($url, $data);
        $res = json_decode($result, true);
        return $res;

    }

    //查询账户额度
    static function sanUserAccount($storeId,$uniacid)
    {
        $config = self::getSansongSet($storeId,$uniacid);
        $data['clientId'] = $config['clientId'];
        $data['shopId'] = $config['shopId'];
        $data['timestamp'] = time() * 1000;
        $appSecrty = $config['appSecrty'];
        $data['sign'] = SanSong::generateSignature($data, $appSecrty);
        $url = $config['domain'] . '/openapi/merchants/v5/getUserAccount';
        $result = SanSong::request_post($url, $data);
        $res = json_decode($result, true);
        if ($res['status'] == 200) {
            return $res['data']['balance'];
        }
        //return $res['msg'];

    }
}
