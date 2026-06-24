<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenusRequest;
use App\Http\Resources\Channel\Menus\Menus;
use App\Models\GoodsCatLabel;
use App\Models\GoodsLabel;
use App\Models\GoodsUnit;
use App\Models\Menu;
use App\Models\RoleMenu;
use App\Models\Store;
use App\Models\StoreGroup;
use Illuminate\Http\Request;
use App\Services\MenuService;
use App\Traits\HelperTrait;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class GoodsUnitController extends ApiController
{

    public function index(Request $request)
    {
        $list = GoodsUnit::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->keyword%");
            })
            ->orderBy('sort', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function show(Request $request, $id)
    {
        $model = GoodsUnit::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }

    public function store(Request $request)
    {
        try {
            $model = new GoodsUnit();
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
            $model = GoodsUnit::where('uniacid', $this->uniacid())->find($id);
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
            $models = GoodsUnit::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
