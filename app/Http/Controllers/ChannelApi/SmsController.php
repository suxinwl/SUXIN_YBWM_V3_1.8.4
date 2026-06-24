<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Models\Admin;
use App\Models\Sms;
use App\Services\ConfigService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsController extends ApiController
{

    public function sendSms(Request $request)
    {
        $sms = new SmsService();
        if ($request->type == 'register') {
            if ($sms->shopRegisterSms($request->mobile, $this->uniacid())) {
                return $this->success([], __('sms.code_success'));
            } else {
                return $this->failed([], __('sms.error'));
            }
        }
    }
}
