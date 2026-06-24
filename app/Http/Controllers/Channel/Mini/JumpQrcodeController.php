<?php

namespace App\Http\Controllers\Channel\Mini;

use App\Http\Controllers\Channel\ApiController;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Storage;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class JumpQrcodeController extends ApiController
{
    public function index()
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->qr_code->list();
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success($res['rule_list']);
    }


    public function store(Request $request)
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->qr_code->getVerifyFile();
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        $file = Storage::disk('index')->put($res['file_name'], $res['file_content']);
        $params = $request->all();
        $res = $app->qr_code->set($params);
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success([],'操作成功');
    }

    public function publish(Request $request)
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->qr_code->publish($request->prefix);
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success([],'操作成功');
    }


    public function destroy(Request $request)
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->qr_code->delete($request->prefix);
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success([],'删除成功');
    }
}
