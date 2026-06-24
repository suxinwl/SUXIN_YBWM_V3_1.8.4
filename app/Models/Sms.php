<?php

namespace App\Models;

use App\Models\Admin\Apply;
use GuzzleHttp\Promise\Is;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Qcloud\Sms\SmsSingleSender;
use Mrgoon\AliSms\AliSms;

class Sms extends BaseModel
{
    //腾讯云发送短信
    public function qcloudSendSms($smsConfig, $phoneNumbers, $templateId, $code, $uniacid = 0)
    {
        if (empty($code)) {
            return false;
        }
        $phoneNumbers = is_array($phoneNumbers)  ? $phoneNumbers : [$phoneNumbers];
        // 短信应用SDK AppID
        $appid = $smsConfig->appid; // 1400开头
        // 短信应用SDK AppKey
        $appkey = $smsConfig->appkey;

        // 需要发送短信的手机号码
        //$phoneNumbers = ["21212313123", "12345678902", "12345678903"];
        $smsSign = $smsConfig->smsSign ?: "腾讯云"; // NOTE: 这里的签名只是示例，请使用真实的已申请的签名，签名参数使用的是`签名内容`，而不是`签名ID`
        try {
            $ssender = new SmsSingleSender($appid, $appkey);
            $params = is_array($code) ? $code : [$code];
            $result = $ssender->sendWithParam(
                "86",
                $phoneNumbers[0],
                $templateId,
                $params,
                $smsSign,
                "",
                ""
            );  // 签名参数不能为空串
            $rsp = json_decode($result, true);
            $log = SmsLog::create([
                'uniacid' => $uniacid,
                'phone' => $phoneNumbers,
                'data' => $templateId,
                'channel' => '',
                'state' =>  $rsp['errmsg'] == 'OK' ? 1 : 0,
                'res' => $rsp
            ]);
            return $rsp['errmsg'] == 'OK' ? true : $rsp['errmsg'];
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    //
    public function aliyunSendSms($smsConfig, $phone, $template_code, $code, $uniacid = 0, $islog = true, $sms_type = '')
    {
        $codeData = ['code' => $code];
        //        if(in_array($sms_type,['user_login','user_registration','retrieve_password','package_expires'])){
        //            $codeData=['code'=>$code];
        //        }
        $aliSms = new AliSms();
        if (empty($code)) {
            return false;
        }
        //var_dump($codeData);die;
        $phone = is_array($phone)  ? $phone : [$phone];
        $mobile = implode(',', $phone);
        $config = [
            'access_key' => $smsConfig->access_key,
            'access_secret' => $smsConfig->access_secret,
            'sign_name' => $smsConfig->sign_name,
        ];
        $response = $aliSms->sendSms($mobile, $template_code, $codeData, $config);
        $data = object_array($response);
        if ($islog) {
            foreach ($phone as $key => $phones) {
                $log = SmsLog::create([
                    'uniacid' => $uniacid,
                    'phone' => $phones,
                    'state' =>  $data['Message'] == 'OK' && $data['Code'] == 'OK' ? 1 : 0,
                    'data' => $template_code,
                    'res' => $data
                ]);
            }
        }
        if ($data['Message'] == 'OK' && $data['Code'] == 'OK') {
            return true;
        } else {
            return $data['Message'];
        }
    }



    //腾讯云发送短信
    public function qcloudSendMessage($smsConfig, $phoneNumbers, $templateId, $data, $uniacid = 0, $islog = true, $channel = '')
    {
        if (empty($data)) {
            return false;
        }
        $phoneNumbers = is_array($phoneNumbers)  ? $phoneNumbers : [$phoneNumbers];
        // 短信应用SDK AppID
        $appid = $smsConfig->appid; // 1400开头
        // 短信应用SDK AppKey
        $appkey = $smsConfig->appkey;

        // 需要发送短信的手机号码
        //$phoneNumbers = ["21212313123", "12345678902", "12345678903"];
        $apply = Apply::find($uniacid);
        if ($apply->smsSign['state'] == 1 && !empty($apply->smsSign['label'])) {
            $smsSign = $apply->smsSign['label'];
        } else {
            $smsSign = $smsConfig->smsSign;
        }
        //$smsSign = $smsConfig->smsSign ?: "腾讯云"; // NOTE: 这里的签名只是示例，请使用真实的已申请的签名，签名参数使用的是`签名内容`，而不是`签名ID`
        try {
            $ssender = new SmsSingleSender($appid, $appkey);
            $result = $ssender->sendWithParam(
                "86",
                $phoneNumbers[0],
                $templateId,
                $data,
                $smsSign,
                "",
                ""
            );  // 签名参数不能为空串
            $rsp = json_decode($result, true);
            foreach ($phoneNumbers as $key => $phone) {
                $log = SmsLog::create([
                    'uniacid' => $uniacid,
                    'phone' => $phone,
                    'data' => $templateId,
                    'channel' => $channel,
                    'state' => $rsp['errmsg'] == 'OK' ? 1 : 0,
                    'res' => $rsp
                ]);
            }
            return $rsp['errmsg'] == 'OK' ? true : $result;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
    public function aliyunSendMessage($smsConfig, $phone, $template_code, $data = [], $uniacid = 0, $islog = true, $channel = '')
    {
        $aliSms = new AliSms();
        if (empty($data)) {
            return false;
        }
        $phone = is_array($phone)  ? $phone : [$phone];
        $mobile = implode(',', $phone);
        $apply = Apply::find($uniacid);
        if (!empty($apply->smsSign['label']) && $apply->smsSign['state'] == 1) {
            $smsSign = $apply->smsSign['label'];
        } else {
            $smsSign = $smsConfig->sign_name;
        }
        $config = [
            'access_key' => $smsConfig->access_key,
            'access_secret' => $smsConfig->access_secret,
            'sign_name' => $smsSign,
        ];
        $response = $aliSms->sendSms($mobile, $template_code, $data, $config);
        $data = object_array($response);
        if ($islog) {
            foreach ($phone as $key => $phones) {
                $log = SmsLog::create([
                    'uniacid' => $uniacid,
                    'phone' => $phones,
                    'data' => $template_code,
                    'channel' => $channel,
                    'state' =>  $data['Message'] == 'OK' && $data['Code'] == 'OK' ? 1 : 0,
                    'res' => $data
                ]);
            }
        }
        if ($data['Message'] == 'OK' && $data['Code'] == 'OK') {
            return true;
        } else {
            return $data['Message'];
        }
    }
}
