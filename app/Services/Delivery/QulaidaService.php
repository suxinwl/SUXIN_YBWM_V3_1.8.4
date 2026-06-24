<?php

namespace App\Services\Delivery;

use App\Models\Delivery\Channel;
use App\Models\Delivery\Order;
use App\Models\Store;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\Make\Application;
use App\Services\ConfigService;
use App\Services\DeliveryService;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use function App\Models\Wechat\Pay\validate;
use App\Models\Order\TakeOutOrder;
class QulaidaService
{
    public $config = [
        'app_id'             => 'qldnzcv7onste7gm3n',
        'team_token' => "KXIFYBHIMA2GF7N7",
        'app_secret'=>'TU23Q3BAOIFIHHAWUJWA9UJPC9KPR5XW'
    ];
    public static function getConfig($storeId, $uniacid)
    {
        $config = DeliveryStore::where('storeId', $storeId)->first();
        $storeId = $config->deliveryType == 1 ? 0 : $storeId;
        $model = Channel::where(function ($q) use ($storeId, $uniacid) {
            return $q->where('storeId', $storeId)->where('uniacid', $uniacid);
        })->where('type', 5)->first();
        if (empty($model)) {
            throw  new BadRequestException('当前门店或者店铺没有趣来达授权');
        }
        return $model;
    }
    /**
     * 获取签名
     * @param $para array 加密的参数数组
     * @param $encKey string 加密的key
     * @return bool|string 生产的签名 sign
     */
    public static function getSign($para, $encKey)
    {
        if (empty($para) || empty($encKey)) {
            return false;
        }
        //除去待签名参数数组中的空值和签名参数
        $para = self::paraFilter($para);
        $para = self::argSort($para);
        $str = self::createLinkstring($para);
        $sign = self::md5Verify($str, $encKey);
        return $sign;
    }

    /**
     * @param $param  array 参数数组
     * @param $encKey string  加密key
     * @param $sign string 签名
     * @return bool 正确 true 错误 false
     * 判断签名是否正确
     */
    public static function isSignCorrect($param, $encKey, $sign)
    {
        if (empty($sign)) {
            return false;
        } else {
            $prestr = self::getSign($param, $encKey);
            return $prestr === $sign ? true : false;
        }
    }

    /**
     * @param $para array 签名参数组
     * @return array 去掉空值与签名参数后的新签名参数组
     * 除去数组中的空值和签名参数
     */
    private static function paraFilter($para)
    {
        $paraFilter = [];
        foreach ($para as $key => $val) {
            if (in_array($key, ["sign", "sign_type", "key"]) || (empty($val) && !is_numeric($val))) { // "",null
                continue;
            } else {
                $paraFilter[$key] = $para[$key];
            }
        }
        return $paraFilter;
    }

    /**
     * @param $para array 排序前的数组
     * @return mixed 排序后的数组
     * 对数组排序
     */
    private static function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * @param $para array 需要拼接的数组
     * @return string 拼接完成以后的字符串
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     */
    private static function createLinkstring($para)
    {
        $arg = "";
        foreach ($para as $key => $val) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = trim($arg, '&');
        //如果存在转义字符，那么去掉转义
        //if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        //}
        return $arg;
    }

    /**
     * @param $prestr string 需要签名的字符串
     * @param $key string 私钥
     * @return string 签名结果
     * 生成签名
     */
    private static function md5Verify($prestr, $key)
    {
        return md5($prestr . $key);
    }



    //获取团队信息
    public static function getTeamInfo()
    {
        $config = ConfigService::getSystemSet('deliverySetting');
        $url='https://api.qolai.com/api/open.Team/getTeamInfo';
        $parmas=[
            'app_id'=>$config['app_id'],
            'version'=>'1',
            'timestamp'=>time(),
            'noncestr'=>wxNonceStr(32),
            'team_token'=>$config['team_token']?:'KXIFYBHIMA2GF7N7',
        ];
        $parmas['sign']=self::getSign($parmas,$config['app_secret']);
        $data = httpRequest($url, $parmas);
        return $data;
    }
    //创建关联商户
    public static function createRelateMerchant($storeId)
    {

        $storeInfo = Store::where('id', $storeId)->first();
        $storeInfo = empty($storeInfo) ? array() : $storeInfo->toArray();
        $config = ConfigService::getSystemSet('deliverySetting');
        $url='https://api.qolai.com/api/open.Merchant/createRelateMerchant';

        $lng=$storeInfo['lng'];$lat=$storeInfo['lat'];
        $regionFormat=$storeInfo['regionFormat'];
        $address=$regionFormat[0].$regionFormat[1].$regionFormat[2].$storeInfo['address'];
        $parmas=[
            'app_id'=>$config->app_id,
            'version'=>'1',
            'timestamp'=>time(),
            'noncestr'=>wxNonceStr(32),
            'team_token'=>$config->team_token?:'KXIFYBHIMA2GF7N7',
            'shop_id'=>$storeInfo['id'],
            'shop_name'=>$storeInfo['name'],
            'shop_tel'=>$storeInfo['mobile'],
            'shop_address'=>$address,
            'shop_tag'=>$lng.','.$lat,
        ];
        $parmas['sign']=self::getSign($parmas,$config->app_secret);

        /*array(4) { ["code"]=> int(100) ["message"]=> string(12) "操作成功" ["data"]=> array(7) { ["merchant_id"]=> int(93511) ["merchant_name"]=> string(19) "武汉雄庄路店1" ["merchant_tel"]=> string(11) "17607186026" ["merchant_photo"]=> string(0) "" ["merchant_address"]=> string(45) "湖北省武汉市武昌区武汉雄庄路店" ["merchant_tag"]=> string(20) "114.305250,30.592760"
        ["merchant_type"]=> int(1) } ["request_id"]=> string(36) "daa46b63-909d-45e4-bc56-9ffd1d0d5333" }*/
        $data = httpRequest($url, $parmas);
        if ($data['code'] != 100) {
            throw new BadRequestHttpException($data['message']);
        }
        return $data['data'];
    }
    //创建订单
    public static function createOrder($storeId,$orderSn)
    {
         $order = TakeOutOrder::where('orderSn', $orderSn)->first();
         $config = ConfigService::getSystemSet('deliverySetting');
         $storeInfo = Store::where('id', $storeId)->first();
         $storeInfo = empty($storeInfo) ? array() : $storeInfo->toArray();
         $lng=$storeInfo['lng'];$lat=$storeInfo['lat'];
         $regionFormat=$storeInfo['regionFormat'];
         $address=$regionFormat[0].$regionFormat[1].$regionFormat[2].$storeInfo['address'];
         $url='https://api.qolai.com/api/open.Order/createOrder';
         $parmas=[
             'app_id'=>$config->app_id,
             'version'=>'1',
             'timestamp'=>time(),
             'noncestr'=>wxNonceStr(32),
             'team_token'=>$config->team_token?:'KXIFYBHIMA2GF7N7',
             'shop_id'=>$storeInfo['id'],
             'shop_name'=>$storeInfo['name'],
             'shop_tel'=>$storeInfo['mobile'],
             'shop_address'=>$address,
             'shop_tag'=>$lng.','.$lat,
             'order_no'=>$orderSn,
             'order_pay_fee'=>'0',
             'pre_times'=>'0',
             'customer_name'=>$order->address['contact'],
             'customer_tel'=>$order->address['mobile'],
             'customer_address'=>$order->address['address'].$order->address['description'],
             'customer_tag'=>$order->address['lng'].','.$order->address['lat'],
             'order_content'=>$order->goodsFormat,
             'is_calc_fee'=>'1'
         ];
        $parmas['sign']=self::getSign($parmas,$config->app_secret);
        $data = httpRequest($url, $parmas);
        return $data;
   }

   //撤销订单
    public static function repealOrder($thirdNo)
    {
        $config = ConfigService::getSystemSet('deliverySetting');
        $url='https://api.qolai.com/api/open.Order/repealOrder';
        $parmas=[
            'app_id'=>$config->app_id,
            'version'=>'1',
            'timestamp'=>time(),
            'noncestr'=>wxNonceStr(32),
            'team_token'=>$config->team_token?:'KXIFYBHIMA2GF7N7',
            'trade_no'=>$thirdNo,
            'reason'=>'取消配送'
        ];
        $parmas['sign']=self::getSign($parmas,$config->app_secret);
        $data = httpRequest($url, $parmas);
        return $data;
    }
    //获取订单详细信息
    public static function getOrderInfo($thirdNo)
    {
        $config = ConfigService::getSystemSet('deliverySetting');
        $url='https://api.qolai.com/api/open.Order/getOrderInfo';
        $parmas=[
            'app_id'=>$config->app_id,
            'version'=>'1',
            'timestamp'=>time(),
            'noncestr'=>wxNonceStr(32),
            'team_token'=>$config->team_token?:'KXIFYBHIMA2GF7N7',
            'trade_no'=>$thirdNo,
        ];
        $parmas['sign']=self::getSign($parmas,$config->app_secret);
        $data = httpRequest($url, $parmas);
        return $data;
    }

    //获取订单进程
    public static function getOrderLog()
    {
        $config = ConfigService::getSystemSet('deliverySetting');
        $url='https://api.qolai.com/api/open.Order/getOrderLog';
        $parmas=[
            'app_id'=>$config->app_id,
            'version'=>'1',
            'timestamp'=>time(),
            'noncestr'=>wxNonceStr(32),
            'team_token'=>$config->team_token?:'KXIFYBHIMA2GF7N7',
            'trade_no'=>'74077411674820608',
        ];
        $parmas['sign']=self::getSign($parmas,$config->app_secret);
        $data = httpRequest($url, $parmas);
        return $data;
    }

    //获取订单骑手坐标
    public static function getCourierTag()
    {
        $config = ConfigService::getSystemSet('deliverySetting');
        $url='https://api.qolai.com/api/open.Order/getCourierTag';
        $parmas=[
            'app_id'=>$config->app_id,
            'version'=>'1',
            'timestamp'=>time(),
            'noncestr'=>wxNonceStr(32),
            'team_token'=>$config->team_token?:'KXIFYBHIMA2GF7N7',
            'trade_no'=>'74077411674820608',
        ];
        $parmas['sign']=self::getSign($parmas,$config->app_secret);
        $data = httpRequest($url, $parmas);
        return $data;
    }

    //获取团队合作的骑手信息
    public static function getCouriers()
    {
        $config = ConfigService::getSystemSet('deliverySetting');
        $url='https://api.qolai.com/api/open.Courier/getCouriers';
        $parmas=[
            'app_id'=>$config->app_id,
            'version'=>'1',
            'timestamp'=>time(),
            'noncestr'=>wxNonceStr(32),
            'team_token'=>$config->team_token?:'KXIFYBHIMA2GF7N7',
        ];
        $parmas['sign']=self::getSign($parmas,$config->app_secret);
        $data = httpRequest($url, $parmas);
        return $data;
    }

    //回调处理
    public function notify()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        //1待发单(订单创建) 2待抢单 3待接单 4取单中(已接单，已抢单) 5送单中(已取单) 6送达订单 7撤销订单
        if($data){
            $res=Order::where('deliverySn',$data['trade_no'])->find();

        }

    }
}
