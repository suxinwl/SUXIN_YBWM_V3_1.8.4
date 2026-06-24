<?php

namespace App\Http\Controllers\Channel;

use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AdController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Ad::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == "begin") {
                    return $q->where("startTime", ">", date("Y-m-d H:i:s", time()));
                }
                if ($request->state == "start") {
                    return $q->where("startTime", "<", date("Y-m-d H:i:s", time()))->where("endTime", ">=", date("Y-m-d H:i:s", time()));
                }
                if ($request->state == "end") {
                    return $q->where("endTime", "<", date("Y-m-d H:i:s", time()));
                }
            })
            ->when($request->name, function ($q) use ($request) {
                return $q->where("name", "like", "%{$request->name}%");
            })
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where("startTime", "<=", $request->startTime);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("endTime", ">=", $request->startTime);
            })
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            // ->orderBy('endTime', 'asc')
            // ->orderBy('sort', 'asc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $model = new Ad();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->isolateStore();
            if ($model->storeId > 0) {
                $model->storeType = 2;
                $model->storeIds = [$this->storeId()];
            }
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = Ad::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
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
            $model = Ad::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            if ($model->storeId > 0) {
                $model->storeType = 2;
                $model->storeIds = [$this->storeId()];
            }
            $model->save();
            return $this->success([], '修改成功');
        } catch (\Exception $e) {
            return $this->failed('修改失败');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = Ad::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
