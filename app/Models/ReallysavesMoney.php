<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ReallysavesMoney extends BaseModel{
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
        $data=self::requestParams($appId,$secret,$url,$params);
        return $data;
    }


    //创建门店
    public static function createStore($appId,$secret,$array){
        $url=self::interfaceAddress().'/api/v1/shop/create';
        $data=self::requestParams($appId,$secret,$url,$array);
        return $data;
    }
    public static function updateStore($appId,$secret,$array){
        $url=self::interfaceAddress().'/api/v1/shop/update';
        $data=self::requestParams($appId,$secret,$url,$array);
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
            'contactName'=>$shop['contact'],
            'outShopId'=>$shop['id'],
            'shopName'=>$shop['name'],
            'shopAddress'=>$shop['address'],
            'cityName'=>'武汉市',
            'industryType'=>1,//发件人经度
            'deliverySupplierList'=>$delivery,//发件人纬度
            'shopLng'=>'114.44272926382254',//物品类别1食品2饮品3鲜花4票务5超市6水果7医药8蛋糕9酒品10服装11汽配12数码13夜宵烧烤14水产15百货99其他
            'contactPhone'=>$shop['storeMobile'],//	坐标类型1高德2百度
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
    public static function createApplication($appId,$secret,$params){
        $url=self::interfaceAddress().'/api/v1/app/sub/create';
        $data=self::requestParams($appId,$secret,$url,$params);
        return $data;
    }

    //查询余额
    public static function walletBalance($appId,$secret){
        $params=[];
        $url=self::interfaceAddress().'/api/v1/wallet/balance';
        $data=self::requestParams($appId,$secret,$url,$params);
        return $data;
    }

    //计算运费
    public  static function calculateFreight($appId,$secret,$params){
        $url=self::interfaceAddress().'/api/v1/order/billing';
        $array=array(
            'appId'=>$appId,
            'timestamp'=>self::getMillisecond(),
            'nonce'=>self::createNonceStr(),
        );

        if($params){
            $array['data']=json_encode($params);
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
        $data=httpRequest($url,$array,$headers,'post');
        return $data;
    }
    //订单详情
    public static function orderDetail($appId,$secret,$outOrderNo)
    {
        $url=self::interfaceAddress().'/api/v1/order/query/detail';
        $params=array(
            'outOrderNo'=>$outOrderNo,//你方订单ID
        );
        $data=self::requestParams($appId,$secret,$url,$params);
        return $data;
    }


    //取消订单
    public static function orderCancel($appId,$secret,$outOrderNo)
    {
        $url=self::interfaceAddress().'/api/v1/order/cancel';
        $params=array(
            'outOrderNo'=>$outOrderNo,//你方订单ID
            'cancelMessage'=>'个人原因',
            'cancelType'=>1
        );
        $data=self::requestParams($appId,$secret,$url,$params);
        return $data;
    }
    public static function  getMillisecond() {
        list($microsecond , $time) = explode(' ', microtime()); //' '中间是一个空格

        return (float)sprintf('%.0f',(floatval($microsecond)+floatval($time))*1000);

    }
    //请求封装
    public static function requestParams($appId,$secret,$url,$data=[]){
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
        $data=httpRequest($url,$array,$headers,'post');
        return $data;
    }
    //账户充值
    public static function accountRecharge($appId,$secret,$params){
        $url=self::interfaceAddress().'/api/v1/wallet/accountRecharge';
        $data=self::requestParams($appId,$secret,$url,$params);
        return $data;
    }
    //创建订单
    public  static function createOrder($appId,$secret,$params){
        $url=self::interfaceAddress().'/api/v1/order/create';
        $array=array(
            'appId'=>$appId,
            'timestamp'=>self::getMillisecond(),
            'nonce'=>self::createNonceStr(),
        );
        if($params){
            $array['data']=json_encode($params);
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
        $data=httpRequest($url,$array,$headers,'post');
        return $data;
    }

}
