<?php

namespace App\Services;

use App\Models\Sms;
use App\Models\SmsAccount;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Overtrue\EasySms\EasySms;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SmsService extends BaseService
{

    public $config;
    public $sms;
    public function __construct()
    {
        $this->config = ConfigService::getSystemSet('sms');
        if (empty($this->config) || $this->config->type == 3) {
            throw new BadRequestHttpException('发送失败，平台暂未设置短信通道，请重试！');
        }
        $this->sms = new Sms();
        if (!config('app.smsDev')) {
            throw new BadRequestHttpException('发送失败，当前站点不支持短信发送');
        }
    }
    // 验证是否手机号码正确
    public function check_phone($phone)
    {
        return true;
        $g = "/^1[3456789]\d{9}$/";
        if (preg_match($g, $phone)) {
            return true;
        }
        return false;
    }

    public function test($template_code, $type)
    {
        $code = randomAESKey();
        if ($this->config->type == 1) {
            $bool = $this->sms->aliyunSendSms($this->config, $this->config->phone, $template_code, $code);
        }
        if ($this->config->type == 2) {
            $bool = $this->sms->qcloudSendSms($this->config, $this->config->phone, $template_code, $code);
        }
        if ($bool === true) {
            return true;
        }
        throw new BadRequestHttpException($bool);
    }

    public function retrieveSms($phone)
    {
        if (!$this->check_phone($phone)) {
            throw new BadRequestHttpException(__('sms.phone_error'));
        }
        $code = randomAESKey();
        if ($this->config->type == 1) {
            $bool = $this->sms->aliyunSendSms($this->config, $phone, $this->config->ali_forgot_template_code, $code);
        }
        if ($this->config->type == 2) {
            $bool = $this->sms->qcloudSendSms($this->config, $phone, $this->config->tx_forgot_template_code, $code);
        }
        if ($bool === true) {
            $key = 'Retrieve.' . $phone;
            Cache::get($key, $code, 300);
            return true;
        }
        throw new BadRequestHttpException($bool);
    }

    public function registerSms($phone)
    {
        if (!$this->check_phone($phone)) {
            throw new BadRequestHttpException(__('sms.phone_error'));
        }
        $code = randomAESKey();
        if ($this->config->type == 1) {
            Log::info($this->config->register_template_code);
            $bool = $this->sms->aliyunSendSms($this->config, $phone, $this->config->ali_register_template_code, $code);
        }
        if ($this->config->type == 2) {
            $bool = $this->sms->qcloudSendSms($this->config, $phone, $this->config->tx_register_template_code, $code);
        }
        if ($bool === true) {
            $key = 'register.' . $phone;
            Cache::put($key, $code, 300);
            return true;
        }
        throw new BadRequestHttpException($bool);
    }


    public static function checkCode($type, $phone, $code)
    {
        if (empty($code) || empty($type) || empty($phone)) {
            return false;
        }
        $key = "$type." . $phone;
        $smsCode = Cache::get($key);
        return $smsCode == $code;
    }

    public function loginSms($phone)
    {
        if (!$this->check_phone($phone)) {
            throw new BadRequestHttpException(__('sms.phone_error'));
        }
        $code = randomAESKey();
        if ($this->config->type == 1) {
            $bool = $this->sms->aliyunSendSms($this->config, $phone, $this->config->ali_login_template_code, $code);
        }
        if ($this->config->type == 2) {
            $bool = $this->sms->qcloudSendSms($this->config, $phone, $this->config->tx_login_template_code, [$code, 5]);
        }
        if ($bool === true) {
            $key = 'register.' . $phone;
            Cache::put($key, $code, 300);
            return true;
        }
        throw new BadRequestHttpException($bool);
    }

    public function applyExpSms($phone, $data = [])
    {
        if (!$this->check_phone($phone)) {
            throw new BadRequestHttpException(__('sms.phone_error'));
        }
        if ($this->config->type == 1) {
            Log::info($this->config->register_template_code);
            //$this->config->register_template_code
            $bool = $this->sms->aliyunSendMessage($this->config, $phone, $this->config->ali_endTime_template_code, $data);
        }
        if ($this->config->type == 2) {
            //$this->config->qcloud_register_template_code
            $bool = $this->sms->qcloudSendMessage($this->config, $phone, $this->config->tx_endTime_template_code, array_values($data));
        }
        if ($bool === true) {
            return true;
        }
        throw new BadRequestHttpException($bool);
    }

    public function sendSms($phone, $template_code = '', $data = [], $uniacid = 0)
    {
        try {
            $channel = $template_code;
            if (!empty($uniacid)) {
                $account = SmsAccount::where('uniacid', $uniacid)->first();
                if ($account->count <= 0) {
                    throw new BadRequestHttpException('短信发送失败');
                }
            }
            if ($this->config->type == 1) {
                Log::info($this->config->$template_code);
                $template_code = 'ali_' . $template_code;
                if (empty($this->config->$template_code)) {
                    throw new BadRequestHttpException('模板不存在');
                }
                $bool = $this->sms->aliyunSendMessage($this->config, $phone, $this->config->$template_code, $data, $uniacid, true, $channel);
            }
            if ($this->config->type == 2) {
                $template_code = 'tx_' . $template_code;
                if (empty($this->config->$template_code)) {
                    throw new BadRequestHttpException('模板不存在');
                }
                //$this->config->qcloud_register_template_code
                $bool = $this->sms->qcloudSendMessage($this->config, $phone, $this->config->$template_code, array_values($data), $uniacid, true, $channel);
            }
            $num = is_array($phone) ? count($phone) : 1;
            if ($bool === true) {
                if ($account) {
                    $account->send_num = $account->send_num + $num;
                    $account->count = $account->count - $num;
                    $account->save();
                }
                return true;
            }
            throw new BadRequestHttpException($bool);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function shopRegisterSms($phone, $uniacid)
    {
        if (!$this->check_phone($phone)) {
            throw new BadRequestHttpException(__('sms.phone_error'));
        }
        try {
            $code = randomAESKey();
            $bool = $this->sendSms($phone, 'register_template_code', ['code' => $code], $uniacid);
            if ($bool === true) {
                $key = 'register.' . $phone;
                Cache::put($key, $code, 300);
                return true;
            }
            throw new BadRequestHttpException($bool);
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}
