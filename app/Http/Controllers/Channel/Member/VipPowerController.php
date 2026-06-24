<?php

namespace App\Http\Controllers\Channel\Member;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Member\VipPower;
use App\Services\DataSeederService;
use DB;
use Illuminate\Http\Request;

class VipPowerController extends ApiController
{

    public function index(Request $request)
    {
        $mode = VipPower::where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->where('type', 'balance')
            ->first();
        if (!$mode) {
            DataSeederService::VipPower($this->uniacid(), $this->isolateStore());
        }
        $list = VipPower::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->keyword%")->orWhere('showName', 'like', "%$request->keyword%");
            })
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function store(Request $request)
    {
        try {
            $model = new VipPower();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->type = "custom";
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = VipPower::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $model = VipPower::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $model = VipPower::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $model->delete();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
