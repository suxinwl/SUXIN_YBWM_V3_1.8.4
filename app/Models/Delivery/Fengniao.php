<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;
use app\models\common\Config;
use App\Models\Store;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fengniao extends BaseModel
{
    const API_URL = 'http://open-anubis.beta.elenet.me/anubis-webapi';

    /**
     * 发送GET请求
     * @param string $url
     * @param array $param
     * @return bool|mixed
     */
    public static function doGet($url, $param = null)
    {
        if (empty($url) or (!empty($param) and !is_array($param))) {
            throw new InvalidArgumentException('Params is not of the expected type');
        }
        // 验证url合法性
//        if (!filter_var($url, FILTER_VALIDATE_URL)) {
//            throw new InvalidArgumentException('Url is not valid');
//        }

        if (!empty($param)) {
            $url = trim($url, '?') . '?' . http_build_query($param);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);     //  不进行ssl 认证
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        var_dump($url);die;
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!empty($result) and $code == 200) {
            return $result;
        }
        return false;
    }

    /**
     * POST请求
     * @param $url
     * @param $param
     * @return boolean|mixed
     */
    public static function doPost($url, $param, $method = "POST")
    {
        // echo 'Request url is ' . $url . PHP_EOL;
        if (empty($url) or empty($param)) {
            throw new InvalidArgumentException('Params is not of the expected type');
        }

        // 验证url合法性
//        if (!filter_var($url, FILTER_VALIDATE_URL)) {
//            throw new InvalidArgumentException('Url is not valid');
//        }

        if (!empty($param) and is_array($param)) {
            $param = urldecode(json_encode($param));
        } else {
            // $param = urldecode(strval($param));
            $param = strval($param);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);     //  不进行ssl 认证

        if (strcmp($method, "POST") == 0) {  // POST 操作
            curl_setopt($ch, CURLOPT_POST, true);
        } else if (strcmp($method, "DELETE") == 0) { // DELETE操作
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        } else {
            throw new InvalidArgumentException('Please input correct http method, such as POST or DELETE');
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: Application/json'));
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!empty($result) and $code == '200') {
            return $result;
        }else{
            echo json_encode(['code'=>2,'msg'=>json_decode($result,true)['message']]);die;
        }
        return false;
    }


    public static function authorize($appId,$secretKey,$merchant_id){
        $code=file_get_contents('fengniao'.$merchant_id.'.log');
        $codeData=json_decode($code,true);
        $code=$codeData['code'];
        $timestamp=self::msectime();
        $params=array(
            'app_id'=>$appId,
            'code'=>$code,
            'grant_type'=>'authorization_code',
            'merchant_id'=>$merchant_id,
            'timestamp'=>$timestamp,
        );
        // 获取签名
        $params['signature'] = self::generateSign($secretKey,$params,$timestamp,'authorization_code');
        $params['timestamp']=$timestamp;
        ksort($params);
        $url='https://open-anubis.ele.me/anubis-webapi/openapi/token';
        $tokenStr=httpRequest($url,http_build_query($params));
        $tokenData=json_decode($tokenStr, true);
        if($tokenData['code']==200){
            $business_data=json_decode($tokenData['business_data'], true);
            $codeData['access_token']=$business_data['access_token'];
            $codeData['app_id']=$business_data['app_id'];
            $codeData['expire_time']=time();
            $codeData['expire_in']=$business_data['expire_in'];
            $codeData['re_expire_in']=$business_data['re_expire_in'];
            $codeData['refresh_token']=$business_data['refresh_token'];
            file_put_contents('fengniao'.$merchant_id.'.log',json_encode($codeData));
        }
        return $business_data['access_token'];
    }



    public static function requestToken($appId,$secretKey,$merchant_id){
        $code=file_get_contents('fengniao'.$merchant_id.'.log');
        if(!$code){
            echo json_encode(['code'=>2,'msg'=>'请先登录蜂鸟开放平台授权商户']);die;
        }
        $codeData=json_decode($code,true);
        //如果token存在,并且刷新时间还是有效的情况下
        if($codeData['access_token']&& bcadd($codeData['expire_time'],$codeData['re_expire_in'])>time()){
            if(bcadd($codeData['expire_time'],$codeData['expire_in'])>time()){
                return  $codeData['access_token'];
            }else{
                //如果有效期小于当前时间,就刷新token
                $timestamp=self::msectime();
                $params=array(
                    'app_id'=>$appId,
                    'grant_type'=>'refresh_token',
                    'merchant_id'=>$merchant_id,
                    'timestamp'=>$timestamp,
                    'refresh_token'=>$codeData['refresh_token']
                );
                // 获取签名
                $params['signature'] = self::generateSign($secretKey,$params,$timestamp,'authorization_code');
                $params['timestamp']=$timestamp;
                $url='https://open-anubis.ele.me/anubis-webapi/openapi/refreshToken';
                $tokenStr=httpRequest($url,http_build_query($params));
                $tokenData=json_decode($tokenStr, true);
                if($tokenData['code']==200){
                    $business_data=json_decode($tokenData['business_data'], true);
                    $codeData['access_token']=$business_data['access_token'];
                    $codeData['app_id']=$business_data['app_id'];
                    $codeData['expire_time']=time();
                    $codeData['expire_in']=$business_data['expire_in'];
                    $codeData['re_expire_in']=$business_data['re_expire_in'];
                    $codeData['refresh_token']=$business_data['refresh_token'];
                    file_put_contents('fengniao'.$merchant_id.'.log',json_encode($codeData));
                }
            }
        }else{
            $access_token=self::authorize($appId,$secretKey,$merchant_id);
            return $access_token;
        }



    }

    //刷新refresh_token
    public static function  refreshToken($appId,$secretKey,$merchant_id,$refresh_token){
        $code=file_get_contents('fengniao'.$merchant_id.'.log');
        if(!$code){
            echo json_encode(['code'=>2,'msg'=>'请先登录蜂鸟开放平台授权商户']);die;
        }
        $codeData=json_decode($code,true);
        $params=array(
            'app_id'=>$appId,
            'grant_type'=>'refresh_token	',
            'merchant_id'=>$merchant_id,
            'refresh_token'=>$refresh_token
        );
        // 获取签名
        $timestamp=self::msectime();
        $seed = $secretKey.'app_id=' . $appId.'&grant_type=authorization_code&merchant_id=' . $merchant_id .'&refresh_token='.$refresh_token.'&timestamp=' . $timestamp;
        $params['signature'] = hash('sha256',htmlspecialchars($seed));
        $params['timestamp']=$timestamp;
        $url='https://open-anubis.ele.me/anubis-webapi/openapi/refreshToken';
        $tokenStr=httpRequest($url,htmlspecialchars(http_build_query($params)));
        $tokenData=json_decode($tokenStr, true);
        if($tokenData['code']==200){
            $business_data=json_decode($tokenData['business_data'], true);
            $codeData['access_token']=$business_data['access_token'];
            $codeData['app_id']=$business_data['app_id'];
            $codeData['expire_time']=time();
            $codeData['expire_in']=$business_data['expire_in'];
            $codeData['re_expire_in']=$business_data['re_expire_in'];
            $codeData['refresh_token']=$business_data['refresh_token'];
            file_put_contents('fengniao'.$merchant_id.'.log',json_encode($codeData));
        }
        return $business_data['access_token'];
    }

    public static function generateSign($secretKey,$params,$timestamp,$grant_type=''){
        if($grant_type){
            $params['grant_type']=$grant_type;
        }
        $params['timestamp']=$timestamp;

        //$str=($secretKey.htmlspecialchars(http_build_query($params)));
        $str=($secretKey.(http_build_query($params)));
        return hash('sha256',$str);
    }
    public static function getFengniaoSet($storeId, $uniacid)
    {
        $storeSet = ConfigService::getStoreConfig('deliveryMode', $storeId);
        $config['app_id'] = $storeSet['fnAppId']?:'2995b9bf-bda7-4022-a29b-8b52f858aad1';
        $config['secret_key'] = $storeSet['fnAppKey']?:'f76e8dca-62d7-4987-aabe-4b1b06f88a39';
        $config['chain_merchant_id'] = $storeSet['fnMerchantId']?:'4103883';
        $config['domain'] = "https://exam-anubis.ele.me";
        $config['chain_store_id']=$storeSet['fnStoreId']?:'204933107';
        //$config['domain'] = "https://open-anubis.ele.me";
        return $config;
    }
    //业务接口验签
    public static function checkPost($order,$business_data,$url){
        $config = self::getFengniaoSet($order['storeId'],$order['uniacid']);
        $appId = $config['app_id'];
        $secretKey=$config['secret_key'];
        $merchant_id=$config['chain_merchant_id'];
        $chain_store_id=$config['chain_store_id'];
        $token = self::requestToken($appId,$secretKey,$merchant_id);
        $business_data_json=json_encode($business_data,JSON_UNESCAPED_UNICODE);
        $timestamp=self::msectime();
        $paramsJson="access_token=$token&app_id=$appId&business_data=$business_data_json&merchant_id=$merchant_id&timestamp=$timestamp&version=1.0";
        $str=$secretKey.$paramsJson;
        $signature= hash('sha256',$str);
        $params=array(
            'access_token'=>$token,
            'app_id'=>$appId,
            'business_data'=>$business_data_json,
            'merchant_id'=>$merchant_id,
            'timestamp'=>$timestamp,
            'version'=>'1.0',
            'signature'=>$signature,
        );

        $data=json_encode($params,JSON_UNESCAPED_UNICODE);
        $result=self::http_post_data($url,$data);
        $data=json_decode($result[1],true);
        return $data;
    }


    public static function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }

    public static function http_post_data($url, $data_string) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json; charset=utf-8",
                "Content-Length: " . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($return_code, $return_content);
    }

}
