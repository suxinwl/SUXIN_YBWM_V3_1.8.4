<?php

namespace App\Http\Controllers\Channel;

use App\Http\Resources\Channel\Menus\Menus;
use App\Models\Spec;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Imports\SpecsImport;
use App\Models\AttrValue;
use App\Models\Goods\SpuAttrValueIds;
use App\Models\Goods\SpuSpecIds;
use App\Models\Goods\SpuSpecValueIds;
use App\Models\SpecValue;

class AttrValueController extends ApiController
{

    public function show(Request $request, $id)
    {
        $model = AttrValue::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }

    public function store(Request $request)
    {
        try {
            $model = AttrValue::where('uniacid', $this->uniacid())->find($request->specId);
            if (empty($model)) {
                throw new BadRequestException('规格不存在');
            }
            AttrValue::Create(['name' => $request->name, 'desc' => $request->desc]);
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $model = AttrValue::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
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
            $models = AttrValue::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $ids = SpuAttrValueIds::where('uniacid', $this->uniacid())->where('valueId', $model->id)->first();
                if ($ids) {
                    return $this->failed('有商品正在使用该属性值，禁止删除');
                }
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
