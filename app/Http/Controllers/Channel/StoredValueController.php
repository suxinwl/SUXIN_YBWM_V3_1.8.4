<?php

namespace App\Http\Controllers\Channel;

use App\Exports\StorevalueOrderDataExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HandleLog;
use App\Models\StoredValue;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class StoredValueController extends ApiController
{
    // GET 索引/列表
    public function index(Request $request)
    {
        $uniacid = $this->uniacid();
        $list = StoredValue::where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->withCount(['order' => function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            }])
            ->when($request->keyword, function ($q) {
                return $q->where('name', 'like', "$q->keyword%");
            })
            ->orderBy('sort', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $model = new StoredValue();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->storeId = 0;
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = StoredValue::where('uniacid', $this->uniacid())
                ->where('storeId', $this->isolateStore())
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
            $model = StoredValue::where('uniacid', $this->uniacid())
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
            $model = StoredValue::where('uniacid', $this->uniacid())
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

    public function orderDataExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $timeArr = $this->timeArr(true);
        $params['timeArr'] = $timeArr;
        return Excel::download(new StorevalueOrderDataExport($params), 'storeValueOrderData.xlsx');
    }


    public function subMessage(Request $request, $id)
    {
        try {
            $model = StoredValue::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            if($model->state==1){
                $model->state=0;
                $model->save();
            }else{
                $model->state=1;
                $model->save();
            }
            return $this->success([], '修改成功');
        } catch (\Exception $e) {
            return $this->failed('修改成功');
        }
    }
}
