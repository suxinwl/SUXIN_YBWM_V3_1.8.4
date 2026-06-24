<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class Douyin extends Model
{
    //获取第三方小程序应用接口调用凭据
    public static function getToken($component_appid, $component_appsecret)
    {
        $component_ticket = Redis::get('component_ticket') ?: '8b235408fba9a9c75d971f077675f037';
        $component_access_token = Redis::get('component_access_token');
        if (!$component_access_token) {
            $url = 'https://open.microapp.bytedance.com/openapi/v1/auth/tp/token?component_appid=' . $component_appid . '&component_appsecret=' . $component_appsecret . '&component_ticket=' . $component_ticket;
            $params = [
                'component_appid' => $component_appid,
                'component_appsecret' => $component_appsecret,
                'component_ticket' => $component_ticket,
            ];
            $response = json_decode(file_get_contents($url), true);
            if ($response['component_access_token']) {
                Redis::setex('component_access_token', 7000, $response['component_access_token']);
                return  $response['component_access_token'];
            }
        }
        return  $component_access_token;
    }

    //获取预授权码
    public static function pre_auth_code($component_appid, $component_access_token)
    {
        $url = 'https://open.microapp.bytedance.com/openapi/v2/auth/pre_auth_code?component_appid=' . $component_appid . '&component_access_token=' . $component_access_token;
        $params = [
            'component_appid' => $component_appid,
            'component_access_token' => $component_access_token,
        ];
        $response = httpRequest($url, $params);
        if ($response['errno']) {
            return $response['message'];
        } else {
            return $response['pre_auth_code'];
        }
    }

    //手动构造授权链接
    public static function authorization($component_appid, $pre_auth_code, $redirect_uri)
    {
        $url = 'https://open.microapp.bytedance.com/mappconsole/tp/authorization?component_appid=' . $component_appid . '&pre_auth_code=' . $pre_auth_code . '&redirect_uri=' . $redirect_uri;
        $response = file_get_contents($url);
        return $response;
    }

    //直接获取授权链接
    public static function gen_link($component_appid, $component_access_token)
    {
        $url = 'https://open.microapp.bytedance.com/openapi/v2/auth/gen_link?component_appid=' . $component_appid . '&component_access_token=' . $component_access_token;
        $params = [
            'component_appid' => $component_appid,
            'component_access_token' => $component_access_token,
        ];
        $response = httpRequest($url, $params);
        var_dump($response);
        die;
        return $response['link'];
    }

    //获取授权小程序接口调用凭据
    public static function token($component_appid, $component_access_token, $authorization_code)
    {
        $authorizer_access_token = Redis::get('authorizer_access_token');
        if (!$authorizer_access_token) {
            $url = 'https://open.microapp.bytedance.com/openapi/v1/oauth/token';
            $params = [
                'component_appid' => $component_appid,
                'component_access_token' => $component_access_token,
                'authorization_code' => $authorization_code,
                'grant_type' => 'app_to_tp_authorization_code'
            ];
            $response = httpRequest($url, $params, [], 'get');
            $authorizer_access_token = $response['authorizer_access_token'];
        }
        return $authorizer_access_token;
    }
    //获取授权小程序列表
    public static function auth_app_list($component_appid, $component_access_token)
    {
        $url = 'https://open.microapp.bytedance.com/openapi/v1/tp/auth_app_list';
        $params = [
            'component_appid' => $component_appid,
            'component_access_token' => $component_access_token,
            'page' => 1,
            'size' => 20
        ];
        $response = httpRequest($url, $params, [], 'get');
        if ($response['errno'] == 0 && $response['message'] == 'success') {
            return $response['data']['authAppList'];
        } else {
            return $response['message'];
        }
    }

    //提交代码
    public static function upload($component_appid, $authorizer_access_token, $template_id, $user_desc, $user_version, $ext_json)
    {
        $url = 'https://open.microapp.bytedance.com/openapi/v1/microapp/package/upload?component_appid=' . $component_appid . '&authorizer_access_token=' . $authorizer_access_token;
        $params = [
            'template_id' => $template_id,
            'user_desc' => $user_desc,
            'user_version' => $user_version,
            'ext_json' => $ext_json,
        ];
        $response = httpRequest($url, $params);
        var_dump($response);
        die;
    }

    //获取授权小程序接口调用凭据
    public static function getAuthorizerToken($component_appid, $component_access_token, $authorization_code)
    {
        $url = 'https://open.microapp.bytedance.com/openapi/v1/oauth/token?component_appid=' . $component_appid . '&component_access_token=' . $component_access_token . '&authorization_code=' . $authorization_code . '&grant_type=app_to_tp_authorization_code';
        $response = json_decode(file_get_contents($url), true);
        $authorizerToken = $response['authorizer_access_token'];
        if ($response['errno']) {
            return $response['message'];
        } else {
            return $authorizerToken;
        }
    }

    //找回授权码
    public static function retrieve($component_appid, $component_access_token, $authorization_appid)
    {
        $url = 'https://open.microapp.bytedance.com/openapi/v1/auth/retrieve?component_appid=' . $component_appid . '&component_access_token=' . $component_access_token . '&authorization_appid=' . $authorization_appid;
        $params = [
            'component_appid' => $component_appid,
            'component_access_token' => $component_access_token
        ];
        $response = httpRequest($url, $params);
        return $response;
    }


    /*验券准备接口
       https://open.douyin.com/goodlife/v1/fulfilment/certificate/prepare/
       验券接口
       https://open.douyin.com/goodlife/v1/fulfilment/certificate/verify/
       撤销核销接口
       https://open.douyin.com/goodlife/v1/fulfilment/certificate/cancel/
       券状态查询接口
       https://open.douyin.com/goodlife/v1/fulfilment/certificate/get/
       券状态批量查询
       https://open.douyin.com/goodlife/v1/fulfilment/certificate/query/
   */
    public static function getClientToken($uniacid)
    {
        $cacheData = Cache::get($uniacid . '_dyToken');
        //if($cacheData){
        // $data=json_decode($cacheData,true);
        // $token=$data['access_token'];
        //}else{
        $data = Config::getSystemSet('tiktok_open_platforms');
        $url = 'https://open.douyin.com/oauth/client_token/';
        $data = ['client_key' => $data->client_key, 'client_secret' => $data->client_secret, 'grant_type' => 'client_credential'];
        $response = httpRequest($url, $data);
        if ($response['message'] == 'success') {
            $token = $response['data']['access_token'];
            $cacheData = ['access_token' => $token];
            Cache::put($uniacid . '_dyToken', json_encode($cacheData), 7000);
        }
        //}
        return $token;
    }

    public static function prepare($uniacid, $code)
    {
        $token = self::getClientToken($uniacid);
        if (strpos($code, 'https') !== false) {
            $url = getRedirectUrl($code);
            $res = parse_url($url);
            parse_str($res['query'], $params);
            $url = 'https://open.douyin.com/goodlife/v1/fulfilment/certificate/prepare/?encrypted_data=' . $params['object_id'];
        } else {
            $url = 'https://open.douyin.com/goodlife/v1/fulfilment/certificate/prepare/?code=' . $code;
        }
        $header = [
            "Content-Type:application/json",
            "access-token:$token"
        ];
        $result = self::httpRequest($url, '', $header);
        $response = json_decode($result, true);
        return $response;
    }

    public static function verify($uniacid, $verify_token, $poi_id, $encrypted_codes)
    {
        $token = self::getClientToken($uniacid);
        $url = 'https://open.douyin.com/goodlife/v1/fulfilment/certificate/verify/';
        $data = [
            'verify_token' => $verify_token,
            'poi_id' => $poi_id,
            'encrypted_codes' => $encrypted_codes
        ];
        $header = [
            "Content-Type:application/json",
            "access-token:$token"
        ];

        $data = json_encode($data);
        $result = self::httpRequest($url, $data, $header);
        $response = json_decode($result, true);
        return $response;
    }

    //撤销核销
    public static function cancel($uniacid, $verify_id, $certificate_id)
    {
        $token = self::getClientToken($uniacid);
        $url = 'https://open.douyin.com/goodlife/v1/fulfilment/certificate/cancel/';
        $data = [
            'verify_id' => $verify_id,
            'certificate_id' => $certificate_id,
        ];
        $header = [
            "Content-Type:application/json",
            "access-token:$token"
        ];

        $data = json_encode($data);
        $result = self::httpRequest($url, $data, $header);
        $response = json_decode($result, true);
        return $response;
    }
    //获取门店信息
    public static function shopQuery($uniacid, $poi_id)
    {
        $token = self::getClientToken($uniacid);

        $url = 'https://open.douyin.com/goodlife/v1/shop/poi/query/?poi_id=' . $poi_id . '&page=1&size=10';
        $header = [
            "Content-Type:application/json",
            "access-token:$token"
        ];
        $result = self::httpRequest($url, null, $header);
        $response = json_decode($result, true);
        return $response;
    }
    static function httpRequest($url, $data = null, $header = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if ($header) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($curl, CURLOPT_HEADER, 0); //返回response头部信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行
        $output = curl_exec($curl);
        //$info = curl_getinfo($curl);
        //print_r($info);die;
        curl_close($curl);
        return $output;
    }
}
