<?php

namespace App\Services;

use App\Models\Sms;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache ;
use Overtrue\EasySms\EasySms;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WechatService extends BaseService
{

    public $config;
    public $sms;
    public function __construct()
    {
        $this->config = ConfigService::getSystemSet('sms');
        $this->sms = new Sms();
    }
    // 验证是否手机号码正确
    public function check_phone($phone)
    {
        $g = "/^1[3456789]\d{9}$/";
        if (preg_match($g, $phone)) {
            return true;
        }
        return false;
    }

    public function retrieveSms($phone)
    {
        if (!$this->check_phone($phone)) {
            throw new BadRequestHttpException(__('sms.phone_error'));
        }
        $code = randomAESKey();
        if ($this->config->type == 1) {
            $bool = $this->sms->aliyunSendSms($this->config, $phone, 'SMS_237390048',$code);
        }
        if ($this->config->type == 2) {
            $bool = $this->sms->qcloudSendSms($this->config, $phone, '1345070',$code);
        }
        if($bool == true){
            $key = 'Retrieve.'.$phone;
            Cache::put($key,$code,300);
            return true;
        }
        throw new BadRequestHttpException($bool);
    }
}
