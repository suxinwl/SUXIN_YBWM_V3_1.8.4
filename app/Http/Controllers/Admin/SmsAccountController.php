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
use App\Services\SmsAccountService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsAccountController extends ApiController
{
    public function index()
    {
    }

    public function update($id)
    {
        $type = Request()->type;
        $res = SmsAccountService::change($id, $type, Request()->value, $this->userId(), Request()->notes);
        return $this->success([], '调整成功');
    }
}
