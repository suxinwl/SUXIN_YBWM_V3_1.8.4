<?php

namespace App\Http\Controllers\Channel;

use App\Models\Drag;
use App\Models\DragStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DragController extends ApiController
{

    public function index(Request $request)
    {
        $query = Drag::select(['id', 'title', 'uniacid', 'notes', 'type', 'appType', 'channel', 'state', 'created_at', 'updated_at', 'releaseTime'])->where('uniacid', $this->uniacid())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('title', 'like', "$request->keyword%");
            })->when($request->appType, function ($q) use ($request) {
                if (in_array($request->appType, [5, 6, 7, 8])) {
                    $q->addSelect(['data']);
                }
                return $q->where('appType', $request->appType);
            })->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })->where('storeId', $this->storeId());
        if (in_array($request->appType, [1, 2, 3])) {
            $list = $query->orderBy('state', 'desc')->orderBy('id', 'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        } else {
            $list = $query->orderBy('id', 'desc')->first();
        }
        return $this->success($list);
    }


    public function show(Request $request, $id)
    {
        $model = Drag::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestHttpException('数据不存在');
        }
        return $this->success($model);
    }

    public function store(Request $request)
    {
        try {
            $model = new Drag();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $msg = "保存成功";
            if ($request->release) {
                Drag::where('uniacid', $this->uniacid())->where('state', 1)->where('appType', $model->appType)->where('type', $model->type)->update(['state' => 0]);
                $model->state = 1;
                $model->releaseTime = date("Y-m-d H:i:s", time());
                $msg = "发布成功";
            }
            $model->save();
            return $this->success($model->id, $msg);
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $model = Drag::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestHttpException('数据不存在');
            }
            if ($request->release == 1) {
                Drag::where('uniacid', $this->uniacid())->where('id', "!=", $id)->where('state', 1)
                ->where('appType', $model->appType)
                ->where('storeId', $model->storeId)
                ->where('type', $model->type)
                ->update(['state' => 0]);
                $model->state = 1;
                $model->releaseTime = date("Y-m-d H:i:s", time());
                $msg = "发布成功";
            }
            $msg = "保存成功";
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], $msg);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }


    public function destroy(Request $request, $id)
    {
        try {
            $model = Drag::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestHttpException('数据不存在');
            }
            $model->delete();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    public function release(Request $request, $id)
    {
        try {
            $model = Drag::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestHttpException('数据不存在');
            }
            Drag::where('uniacid', $this->uniacid())
                ->where('state', 1)
                ->where('appType', $model->appType)
                ->where('storeId', $this->storeId())
                ->where('type', $model->type)->update(['state' => 0]);
            $model->state = 1;
            $model->releaseTime = date("Y-m-d H:i:s", time());
            $model->save();
            return $this->success([], '发布成功');
        } catch (\Exception $e) {
            return $this->failed('发布失败');
        }
    }

    public function getRelease(Request $request, $type)
    {
        try {
            $model = Drag::select(['id', 'title', 'uniacid', 'notes', 'type', 'appType', 'channel', 'state', 'created_at', 'updated_at', 'releaseTime'])
                ->where('uniacid', $this->uniacid())
                ->where("appType", $type)
                ->where('storeId', $this->storeId())
                ->where("state", 1)->first();
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('发布失败');
        }
    }

    public function tempList(Request $request)
    {
        $list = DragStorage::select(['id', 'name', 'logo'])->orderBy('id', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function saveTemp(Request $request, $id)
    {
        $data = DragStorage::find($id);
        if (empty($data)) {
            return $this->failed('模板不存在');
        }
        $data->data = str_replace('https://ybv3.b-ke.cn/storage/1/uploads/2023/05/09/', Storage::disk('public')->url('default/drag/1/'), $data->data);
        $data->data = str_replace('https://ybv3.b-ke.cn/storage/1/uploads/2023/05/10/', Storage::disk('public')->url('default/drag/1/'), $data->data);
        $data->data = str_replace('http://ybwm.oss-cn-beijing.aliyuncs.com/1/uploads/2023/06/10/', Storage::disk('public')->url('default/drag/1/'), $data->data);
        $data->data = json_decode($data->data, true);
        $model = Drag::create([
            'title' => $data->name,
            'uniacid' => $this->uniacid(),
            'type' => NULL,
            'data' => $data->data,
            'appType' => 1,
            'channel' => 1,
            'storeId' => $this->storeId(),
            'state' => 0,
            'notes' => $data->name
        ]);
        return $this->success($model->id);
    }
}
