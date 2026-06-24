<?php

namespace App\Http\Controllers\Channel;

use App\Http\Resources\Channel\Menus\Menus;
use App\Models\Spec;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Imports\SpecsImport;
use App\Models\SpecValue;
use Maatwebsite\Excel\Facades\Excel;

class SpecController extends ApiController
{
    public function index(Request $request)
    {
        $list = Spec::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->keyword%");
            })
            ->orderBy('sort', 'asc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function show(Request $request, $id)
    {
        $model = Spec::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }

    public function store(Request $request)
    {
        try {
            $model = new Spec();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->save();
            foreach ($request->value as $key => $spec) {
                $spec['uniacid'] = $this->uniacid();
                $spec['specId'] = $model->id;
                SpecValue::Create($spec);
            }
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $model = Spec::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            foreach ($request->value as $key => $spec) {
                if (!isset($spec['id'])) {
                    $spec['uniacid'] = $this->uniacid();
                    $spec['specId'] = $model->id;
                    SpecValue::Create($spec);
                } else {
                    SpecValue::where('id', $spec['id'])->update(['name' => $spec['name'], 'img' => $spec['img']]);
                }
            }
            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }


    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = Spec::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                if ($model->value()->count() > 0) {
                    throw new BadRequestException('请先删除规格值');
                }
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function switch(Request $request, $id)
    {
        try {
            $model = Spec::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->imgSwitch = $model->imgSwitch == 1 ? 0 : 1;
            $model->save();
            return $this->success();
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
