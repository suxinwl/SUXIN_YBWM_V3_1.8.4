<?php

namespace App\Http\Controllers\Admin\Mini;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Mini\Register;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class RegisterController extends ApiController
{
    public function index(Request $request)
    {
        $list = Register::where('uniacid', 0)
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $lockKey = 'miniRegister';
        $isLocked = Cache::lock($lockKey);
        if (!$isLocked->get()) {
            throw new BadRequestException('请勿重复提交');
        }
        try {
            $app = AdminOpenWechat::openPlatform();
            $model = Register::create($request->all());
            if ($model->type == 1) {
                $res = $app->component->registerMiniProgram($model->postData);
                if ($res['errcode'] != 0) {
                    $model->state = 2;
                    $model->status = $res['errcode'];
                    $model->msg = $res['errmsg'];
                    $model->save();
                    throw new BadRequestException($res['errmsg']);
                }
            }
            if ($model->type == 2) {
                $res = $app->component->httpPostJson('wxa/component/fastregisterbetaweapp', ['name' => $model->appName, 'openid' => $model->openid]);
                if ($res['errcode'] != 0) {
                    $model->state = 2;
                    $model->status = $res['errcode'];
                    $model->msg = $res['errmsg'];
                    $model->save();
                    throw new BadRequestException($res['errmsg']);
                }
                $model->unique_id = $res['unique_id'];
                $model->authorize_url = $res['authorize_url'];
                $model->save();
                $res = $app->component->httpPostJson('wxa/verifybetaweapp', $model->postData);
                if ($res['errcode'] != 0) {
                    $model->state = 2;
                    $model->status = $res['errcode'];
                    $model->msg = $res['errmsg'];
                    $model->save();
                    throw new BadRequestException($res['errmsg']);
                }
            }
            optional($isLocked)->release();
            return $this->success('申请成功请等待审核');
        } catch (\Exception $e) {
            optional($isLocked)->release();
            return $this->failed($e->getMessage());
        } finally {
            optional($isLocked)->release();
        }
    }

    public function update(Request $request, $id)
    {
        $register = Register::find($id);
        if (empty($register)) {
            return $this->failed('数据不存在');
        }
        $lockKey = 'miniRegister';
        $isLocked = Cache::lock($lockKey);
        if (!$isLocked->get()) {
            throw new BadRequestException('请勿重复提交');
        }
        try {
            $app = AdminOpenWechat::openPlatform();
            if ($register->type == 1) {
                $model = Register::create($request->all());
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
            }
            if ($register->type == 2) {
                if ($register->unique_id) {
                    $res = $app->component->httpPostJson('wxa/verifybetaweapp', $model->postData);
                    if ($res['errcode'] != 0) {
                        $model->state = 2;
                        $model->status = $res['errcode'];
                        $model->msg = $res['errmsg'];
                        $model->save();
                        throw new BadRequestException($res['errmsg']);
                    }
                }
            }
            optional($isLocked)->release();
            return $this->success('申请成功请等待审核');
        } catch (\Exception $e) {
            optional($isLocked)->release();
            return $this->failed($e->getMessage());
        } finally {
            optional($isLocked)->release();
        }
    }

    public function destroy(Request $request, $id)
    {
        $model = Register::where('state', 2)
            ->find($id);
        if (empty($model)) {
            return $this->failed('记录不存在或状态正在审核中，无法删除');
        }
        $model->delete();
        return $this->success('删除成功');
    }
}
