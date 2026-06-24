<?php
namespace App\Services\Delivery;
use App\Models\Delivery\Channel;
use App\Models\Delivery\Store;
use App\Models\TakeOut\Delivery;
use App\Models\Wechat\Make\Application;
use App\Services\DeliveryService;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
class ReallysavesMoneyService{
    public static function interfaceAddress()
    {
        return  'https://openapi.wlhulian.com';
        //return  'https://testapi.wlhulian.com';
    }
    //查看送货员详情
    public static function courier($outOrderNo){
        $params=['outOrderNo'=>$outOrderNo];
        $url=self::interfaceAddress().'/api/v1/order/query/courier';
        $data=self::requestParams($url,$params);
        return $data;
    }
    //查询运力
    public static function supplierQuery($appId,$secret){
        $params=[];
        $url=self::interfaceAddress().'/api/v1/supplier/query';
        $data=self::requestParams($url,$params,$appId,$secret);
        return $data;
    }


    //创建门店
    public static function createStore($shop,$industryType,$cityName,$appId,$secret){
        $supplier=self::supplierQuery($appId,$secret);

        $delivery=array_column(json_decode($supplier,true)['data'],'deliveryCode');
        file_put_contents('createStore.txt',$shop['id'].'创建发货店铺'.PHP_EOL, FILE_APPEND);
        $params=array(
            'contactName'=>$shop['storeLinkMan'],
            'outShopId'=>$shop['id'],
            'callOrderType'=>1,
            'shopName'=>$shop['name'],
            'shopAddress'=>$shop['address'],
            'cityName'=>$cityName,
            'industryType'=>$industryType,
            'deliverySupplierList'=>$delivery,//发件人纬度
            'coordinateType'=>1,
            'shopLng'=>$shop['lng'],
            'contactPhone'=>$shop['storeTel'],
            'shopAddressDetail'=>$shop['address'],
            'shopLat'=>$shop['lat'],
        );
        $url=self::interfaceAddress().'/api/v1/shop/create';
        $data=self::requestParams($url,$params,$appId,$secret);
        return $data;
    }
    //同步门店
    public static function syncShop($shop){
        $supplier=self::supplierQuery();
        if($supplier['code']==200){
            $delivery=array_column($supplier['data'],'deliveryCode');
        }else{
            return false;
        }

        $params=array(
            'contactName'=>$shop['storeLinkMan'],
            'outShopId'=>$shop['id'],
            'shopName'=>$shop['name'],
            'shopAddress'=>$shop['address'],
            'cityName'=>'武汉市',
            'industryType'=>1,//发件人经度
            'deliverySupplierList'=>$delivery,//发件人纬度
            'shopLng'=>'114.44272926382254',//物品类别1食品2饮品3鲜花4票务5超市6水果7医药8蛋糕9酒品10服装11汽配12数码13夜宵烧烤14水产15百货99其他
            'contactPhone'=>$shop['storeTel'],//	坐标类型1高德2百度
            'shopAddressDetail'=>$shop['address'],//	坐标类型1高德2百度
            'shopLat'=>'30.65365610977412',//	坐标类型1高德2百度
        );
        $url=self::interfaceAddress().'/api/v1/shop/create';
        $data=self::requestParams($url,$params);
        return $data;
    }

    //查询发货店铺详情
    public static function queryStore($shop){
        $params=array(
            'outShopId'=>$shop['id']
        );
        $url=self::interfaceAddress().'/api/v1/shop/query';
        $data=self::requestParams($url,$params);
        return $data;
    }
    //随机生成字符串
    public static function createNonceStr($length = 8)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return "z".$str;
    }

    //创建真省钱应用
    public static function createApplication($name,$phone){
        $callbackUrl=Yii::$app->request->hostInfo.'/channel/notify/reallysavesmoeny';
        $params=[
            'name'=>$name,
            'phone'=>$phone,
            'callbackUrl'=>$callbackUrl
        ];
        $url=self::interfaceAddress().'/api/v1/app/sub/create';
        $data=self::requestParams($url,$params);
        return $data;
    }

    //查询余额
    public static function walletBalance($appId,$secret){
        $params=[];
        $url=self::interfaceAddress().'/api/v1/wallet/balance';
        $data=self::requestParams($url,$params,$appId,$secret);
        return $data;
    }

    //计算运费
    public  static function calculateFreight($order,$shopId,$supplierCode='',$appId,$secret){
        $url=self::interfaceAddress().'/api/v1/order/billing';
        $params=array(
            'supplierCode'=>$supplierCode,
            'outShopCode'=>1,
            'outOrderNo'=>$order['outTradeNo'],
            "shopId"=>$shopId,
            'toAddress'=>$order['receivedAddress'],
            'toAddressDetail'=>$order['receivedAddress'],
            "toLng"=>$order['lng'], //收件经度， 目前只支持百度坐标
            "toLat"=>$order['lat'], //收件纬度, 目前只支持百度坐标
            "toReceiverName"=>$order['receivedName']?:'viva', //收件人姓名
            "toMobile"=>$order['receivedTel'], //收件人联系方式
            'goodType'=>9,
            'weight'=>1
        );
        $data=self::requestParams($url,$params,$appId,$secret);
        return $data;
    }
    //订单详情
    public static function orderDetail($outOrderNo)
    {
        $url=self::interfaceAddress().'/api/v1/order/query/detail';
        $params=array(
            'outOrderNo'=>$outOrderNo,//你方订单ID
        );
        $data=self::requestParams($url,$params);
        return $data;
    }
    public static function  getMillisecond() {
        list($microsecond , $time) = explode(' ', microtime()); //' '中间是一个空格

        return (float)sprintf('%.0f',(floatval($microsecond)+floatval($time))*1000);

    }
    //请求封装
    public static function requestParams($url,$data=[],$appId='',$secret=''){
        if($appId==''&&$secret==''){
            $reallyData=Config::getSystemSet('reallysavesmoney',0);
            $appId=$reallyData['appId']?:'8075dfd334f7435fa98817bd4c3bcf0a';
            $secret=$reallyData['secret']?:'587bd9f20b0f43919b9473acab4d22fe';
        }
        $array=array(
            'appId'=>$appId,
            'timestamp'=>self::getMillisecond(),
            'nonce'=>self::createNonceStr(),
        );

        if($data){
            $array['data']=json_encode($data);
        }else{
            $array['data']='null';
        }

        $sign=Md5($secret.$array['timestamp'].$array['nonce'].$array['data']);
        $array['sign']=$sign;
        //$array=json_encode($array);
        $headers = array(
            "Content-Type:application/json",
            "Accept:*/*"
        );
        $data=httpRequest($url,json_encode($array),$headers,'post');
        return $data;
    }
    //账户充值
    public static function accountRecharge($appId='',$secret='',$rechargePrice,$rechargeType){
        file_put_contents('storeValue.txt',$appId.'充值'.$rechargePrice.PHP_EOL, FILE_APPEND);
        $params=['rechargePrice'=>$rechargePrice,'rechargeType'=>$rechargeType];
        $url=self::interfaceAddress().'/api/v1/wallet/accountRecharge';
        $data=self::requestParams($url,$params,$appId,$secret);
        return $data;
    }
    //创建订单
    public  static function createOrder($order,$shopId,$supplierCode=[],$appId='',$secret=''){
        $url=self::interfaceAddress().'/api/v1/order/create';
        $params=array(
            "outOrderNo"=>$order['outTradeNo'], //接入方平台订单号
            //"estimatePrice"=> 1000, //比价金额  单位分,用来校验金额有没有发生变化
            "multipleSupplierCodes"=> $supplierCode, //发单渠道编号（不填则为默认返回店铺全部可用的运力）
            "outShopCode"=> "1", //发货门店 接入方门店编号(店到点模式下，与平台方编号必填一个)
            "shopId"=>$shopId, //发货门店 平台方门店编号（店到点模式下，与平台方编号必填一个）
            "toAddress"=>$order['receivedAddress'], //收件地址
            "toAddressDetail"=>$order['receivedAddress'], //收件人详细地址
            "toLng"=>$order['lng'], //收件经度， 目前只支持百度坐标
            "toLat"=>$order['lat'], //收件纬度, 目前只支持百度坐标
            "toReceiverName"=>$order['receivedName']?:'viva', //收件人姓名
            "toMobile"=>$order['receivedTel'], //收件人联系方式
            "goodType"=> 9,
            "weight"=>1 //物品重量,单位KG
        );
        $data=self::requestParams($url,$params,$appId,$secret);
        return $data;
    }

    public function cancelOrder($outTradeNo,$appId,$secret){
        $url=self::interfaceAddress().'/api/v1/order/cancel';
        $params=array(
            "cancelMessage"=>'取消订单', //接入方平台订单号
            "cancelType"=>1,//(1,"个人原因"), (2, "骑手配送不及时"), (3, "骑手无法配送"), (4, "骑手取货不及时"), (20, "其他"),
            "outOrderNo"=>$outTradeNo
        );
        $data=self::requestParams($url,$params,$appId,$secret);
        return $data;

    }
}
