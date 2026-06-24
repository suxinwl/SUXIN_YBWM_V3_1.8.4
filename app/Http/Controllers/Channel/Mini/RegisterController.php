<?php

namespace App\Http\Controllers\Channel\Mini;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Mini\Register;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class RegisterController extends ApiController
{
    public function index(Request $request)
    {
        $list = Register::where('uniacid', $this->uniacid())
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        
        $app = AdminOpenWechat::openPlatform();
        $model = Register::create(array_merge($request->all(), ['uniacid' => $this->uniacid()]));
        $res = $app->component->registerMiniProgram($model->postData);
        if ($res['errcode'] != 0) {
            $model->state = 2;
            $model->status = $res['errcode'];
            $model->msg = $res['errmsg'];
            $model->save();
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success('申请成功请等待审核');
    }

    public function update(Request $request, $id)
    {
        $register = Register::find($id);
        if (empty($register)) {
            return $this->failed('数据不存在');
        }
        $app = AdminOpenWechat::openPlatform();
        $model = Register::create(array_merge($request->all(), ['uniacid' => $this->uniacid()]));
        if ($model) {
            $register->delete();
        }
        $res = $app->component->registerMiniProgram($model->postData);
        if ($res['errcode'] != 0) {
            $model->state = 2;
            $model->status = $res['errcode'];
            $model->msg = $res['errmsg'];
            $model->save();
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success('申请成功请等待审核');
    }

    public function destroy(Request $request, $id)
    {
        $model = Register::where('uniacid', $this->uniacid())
            ->where('state', 2)
            ->find($id);
        if (empty($model)) {
            return $this->failed('记录不存在或状态正在审核中，无法删除');
        }
        $model->delete();
        return $this->success('删除成功');
    }
}
