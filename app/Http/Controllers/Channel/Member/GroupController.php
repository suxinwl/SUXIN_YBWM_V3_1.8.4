<?php

namespace App\Http\Controllers\Channel\Member;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Member\Group;
use App\Models\Member\Vip;
use App\Models\Member\VipPower;
use Illuminate\Http\Request;

class GroupController extends ApiController
{

    public function index(Request $request)
    {
        $list = Group::withCount(['member'])->where('uniacid', $this->uniacid())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%{$request->keyword}%");
            })
            ->where('storeId', $this->storeId())
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function store(Request $request)
    {
        try {
            $model = new Group();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = Group::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->find($id);
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
            $model = Group::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            return $this->failed('更新失败');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            Group::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->whereIn('id', $idArray)->delete();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
