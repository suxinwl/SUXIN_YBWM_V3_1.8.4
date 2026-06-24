<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
class Kuaishou extends Model{
//appKey:
//ks697213521555654560
//appSecret:
//soW0WS02wKeuqc3yErPM-w
//signSecret:
//b1e6950a7b01a676aac826fb2652a519
//消息秘钥:
//noLS89srMjaU1rhTYNMGkQ==

//13570995626

    public static function getClientToken(){
        $token=Cache::get('ksToken');
        if($token){
            return  $token;
        }else{
            $token=self::refreshToken();;
            Cache::put('ksToken',$token,170000);
        }
        return $token;
    }

    public static function refreshToken  (){
        $data=Config::getSystemSet('kuaishou_open_platforms');
        $data=object_array($data);
        $refresh_token=$data['refresh_token'];
        $app_id=$data['appKey'];;
        $app_secret=$data['appSecret'];;
        $url='https://lbs-open.kuaishou.com/oauth2/refresh_token?grant_type=refresh_token&refresh_token='.$refresh_token.'&app_id='.$app_id.'&app_secret='.$app_secret;
        $row=file_get_contents($url);
        $row=json_decode($row,true);
        if($row['result']==1){
            $data['access_token']=$row['access_token'];
            $data['access_token_expires_time']=time()+170000;
            $data['refresh_token']=$row['access_token'];
            $data['refresh_token_expires_time']=time()+15550000;
            $res = Config::where('ident','kuaishou_open_platforms')->first();
            if ($res) {
                $model = Config::find($res->id);
                $model->data = json_encode($data);
                $model->save();
            }
            $token=$row['access_token'];
            Cache::put('ksToken',$token,170000);
        }
        return $token;
    }
    public static function prepare($code){
        $token=self::getClientToken();
        if(strpos($code, 'https') !== false){
            $url='https://lbs-open.kuaishou.com/goodlife/v1/fulfilment/certificate/prepare?encrypted_data='.$code;
            $url=urlencode($url);
        } else {
            $url='https://lbs-open.kuaishou.com/goodlife/v1/fulfilment/certificate/prepare?code='.$code;
        }
        $header=[
            "Content-Type:application/json",
            "access-token:$token"
        ];
        $result=self::httpRequest($url,'',$header);
        $response=json_decode($result,true);
        return $response;
    }

    public static function verify($verify_token,$poi_id,$encrypted_codes,$order_id){
        $token=self::getClientToken();
        $url='https://lbs-open.kuaishou.com/goodlife/v1/fulfilment/certificate/verify';
        $data=[
            'verify_token'=>$verify_token,
            'poi_id'=>$poi_id,
            'encrypted_codes'=>$encrypted_codes,
            'order_id'=>$order_id,
        ];
        $header=[
            "Content-Type:application/json",
            "access-token:$token"
        ];

        $data=json_encode($data);
        $result=self::httpRequest($url,$data,$header);
        $response=json_decode($result,true);
        return $response;
    }

    //撤销核销
    public static function cancel($verify_id,$certificate_id){
        $token=self::getClientToken();
        $url='https://lbs-open.kuaishou.com/goodlife/v1/fulfilment/certificate/cancel';
        $data=[
            'verify_id'=>$verify_id,
            'certificate_id'=>$certificate_id,
        ];
        $header=[
            "Content-Type:application/json",
            "access-token:$token"
        ];

        $data=json_encode($data);
        $result=self::httpRequest($url,$data,$header);
        $response=json_decode($result,true);
        return $response;
    }
    //获取门店信息
    public static function shopQuery($poi_id){
        $token=self::getClientToken();

        $url='https://lbs-open.kuaishou.com/goodlife/v1/shop/poi/query?poi_id='.$poi_id.'&page=1&size=10';
        $header=[
            "Content-Type:application/json",
            "access-token:$token"
        ];
        $result=self::httpRequest($url,null,$header);
        $response=json_decode($result,true);
        return $response;
    }
    static function httpRequest($url, $data = null,$header=null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if($header) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($curl, CURLOPT_HEADER, 0);//返回response头部信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行
        $output = curl_exec($curl);
        //$info = curl_getinfo($curl);
        //print_r($info);die;
        curl_close($curl);
        return $output;
    }
}
