<?php

namespace App\Http\Controllers\Channel\Mini;

use App\Http\Controllers\Channel\ApiController;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class OrderPathInfoController extends ApiController
{
    public function index()
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->httpPostJson('wxa/security/getorderpathinfo',[
            'info_type'=>1
        ]);
        if ($res['errcode'] == 0) {
            $data[] =$res['msg'];
        }
        return $this->success($data);
    }

    public function store(Request $request)
    {
        $app = AdminOpenWechat::openPlatform();
        $config = ChannelOpenWechat::getConfig($this->uniacid());
        $res = $app->httpPostJson('wxa/security/applysetorderpathinfo',[
            'batch_req'=>[
                'path'=>"pages/index/order-index",
                'appid_list'=>[
                    $config->authorizer_appid
                ],
            ]
        ]);
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success([],"提交成功,请等待审核");
    }
}
