<?php

namespace App\Http\Controllers\Channel\Mini;

use App\Http\Controllers\Channel\ApiController;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Client\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SecurityController extends ApiController
{

    /**
     *开启同城配送
     */
    public function open()
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->httpPostJson('cgi-bin/express/local/business/open');
        if ($res['resultcode'] != 0) {
            throw new BadRequestException($res['resultmsg']);
        }
        return $this->success([], '开通成功');
    }

    /**
     * 支持同城配送列表
     */
    public function index()
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $this->httpPostJson('cgi-bin/express/local/business/delivery/getall');
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success($res['list']);
    }

    /**
     * 绑定账号
     */
    public function store(Request $request, $id)
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $this->httpPostJson('cgi-bin/express/local/business/delivery/getall');
        if ($res['resultcode'] != 0) {
            throw new BadRequestException($res['resultmsg']);
        }
        return $this->success('申请成功请等待审核');
    }

    /**
     * 已绑定的账号
     */
    public function bindAccount()
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $this->httpPostJson('cgi-bin/express/local/business/shop/get');
        if ($res['resultcode'] != 0) {
            throw new BadRequestException($res['resultmsg']);
        }
        return $this->success($res['shop_list']);
    }
}
