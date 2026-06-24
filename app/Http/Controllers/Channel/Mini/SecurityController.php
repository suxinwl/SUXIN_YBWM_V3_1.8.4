<?php

namespace App\Http\Controllers\Channel\Mini;

use App\Http\Controllers\Channel\ApiController;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Client\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SecurityController extends ApiController
{
    public function index()
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->security->get();
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success($res['interface_list']);
    }

    public function store(Request $request)
    {
        $app = AdminOpenWechat::openPlatform();
        $res = $app->component->registerMiniProgram($request->all());
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success('申请成功请等待审核');
    }
}
