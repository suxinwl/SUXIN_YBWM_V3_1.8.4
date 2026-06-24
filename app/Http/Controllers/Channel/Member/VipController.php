<?php

namespace App\Http\Controllers\Channel\Member;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Member\Vip;
use App\Models\Member\VipPower;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VipController extends ApiController
{

    public function index(Request $request)
    {
        $list = Vip::withCount(['member'])->where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->when($request->keyword, function ($q) {
                return $q->where('name', 'like', "$q->keyword%")->orWhere('showName', 'like', "$q->keyword%");
            })
            ->when($request->isAll, function ($q) {
                return $q->withTrashed();
            })
            ->orderBy('level', 'asc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function store(Request $request)
    {
        try {
            $model = new Vip();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->isolateStore();
            $model->level = $model->getLevel();
            //$model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = Vip::withTrashed()->where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $model->weekArr=$model->weekArr?:[];
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $model = Vip::where('uniacid', $this->uniacid())
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
            return $this->failed($e->getMessage());
        }
    }

    public function destory(Request $request, $id)
    {
        try {
            $model = Vip::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $model->delete();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    public function state(Request $request, $id)
    {
        try {
            $model = Vip::withTrashed()->where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }

            if ($model->deleted_at) {
                $ext = DB::table('member_vip')->whereNotNull('deleted_at')
                    ->where('uniacid', $this->isolateStore())
                    ->where('uniacid', $this->uniacid())->where('level', intval($model->level) - 1)
                    ->first();
                if ($ext) {
                    return $this->failed("{$ext->name}(Vip{$ext->level})还未开启,请先开启后再操作");
                }
                $model->restore();
            } else {
                $ext = DB::table('member_vip')->whereNull('deleted_at')
                    ->where('uniacid', $this->isolateStore())
                    ->where('uniacid', $this->uniacid())->where('level', intval($model->level) + 1)->first();
                if ($ext) {
                    return $this->failed("{$ext->name}(Vip{$ext->level})还未关闭,请先关闭后再操作");
                }
                $model->delete();
            }
            return $this->success([], '操作成功');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->failed('失败');
        }
    }
}
