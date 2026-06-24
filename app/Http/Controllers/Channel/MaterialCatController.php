<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenusRequest;
use App\Http\Resources\Channel\Menus\Menus;
use App\Models\GoodsCatLabel;
use App\Models\GoodsLabel;
use App\Models\GoodsMark;
use App\Models\Material;
use App\Models\MaterialCat;
use App\Models\Menu;
use App\Models\RoleMenu;
use App\Models\Store;
use App\Models\StoreGroup;
use Illuminate\Http\Request;
use App\Services\MenuService;
use App\Traits\HelperTrait;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class MaterialCatController extends ApiController
{

    public function index(Request $request)
    {
        $list = MaterialCat::with(['materialList'])
            ->where('storeId', $this->storeId())
            ->where('uniacid', $this->uniacid())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->keyword%");
            })->orderBy('sort', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function show(Request $request, $id)
    {
        $model = MaterialCat::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }

    public function store(Request $request)
    {
        try {
            $model = new MaterialCat();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $model = MaterialCat::where('uniacid', $this->uniacid())->find($id);
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
            MaterialCat::where('uniacid', $this->uniacid())->where('id', $id)->delete();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    public function state(Request $request, $id)
    {
        try {
            $model = MaterialCat::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->state =  $model->state == 1 ? 0 : 1;
            $model->save();
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }
}
