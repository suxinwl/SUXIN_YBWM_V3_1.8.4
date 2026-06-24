<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Admin\MobileLoginPost;
use App\Http\Resources\Admin\Sms\SmsCollection;
use App\Models\Admin;
use App\Models\Sms;
use App\Models\SmsLog;
use App\Services\ConfigService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsController extends ApiController
{

    public function test()
    {
        $sms = new SmsService();
        if ($sms->test($request->template_code, $request->type)) {
            return $this->success([], __('sms.code_success'));
        } else {
            return $this->failed([], __('sms.error'));
        }
    }


    public function retrieve(Request $request)
    {
        $user = Admin::where('mobile', $request->phone)->first();
        if (empty($user)) {
            return $this->failed(__('base.not_user'));
        }
        $sms = new SmsService();
        if ($sms->registerSms($request->phone)) {
            return $this->success([], __('sms.code_success'));
        } else {
            return $this->failed([], __('sms.error'));
        }
    }

    public function login(Request $request)
    {
        $user = Admin::where('mobile', $request->mobile)->where('id',1)->first();
        if (empty($user)) {
            return $this->failed('发送失败,该手机号无登录权限');
        }
        $sms = new SmsService();
        if ($sms->loginSms($request->mobile)) {
            return $this->success([], __('sms.code_success'));
        } else {
            return $this->failed([], __('sms.error'));
        }
    }
}
