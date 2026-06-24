<?php

namespace App\Http\Controllers\Channel\Delivery;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Store;
use App\Models\GoodsRecommend\Goods;
use App\Models\GoodsRecommend\Recommend;
use App\Models\GoodsSku;
use App\Models\Recipe\RecipeCategory;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeGoods;
use App\Models\Recipe\RecipeGoodsSku;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class RuleController extends ApiController
{

    public function index(Request $request)
    {
        $list = Rule::where('uniacid', $this->uniacid())
            ->when($request->name, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->name%");
            })
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }



    public function store(Request $request)
    {
        try {
            $model = new Rule();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = Rule::where('uniacid', $this->uniacid())->find($id);
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
            $model = Rule::where('uniacid', $this->uniacid())->find($id);
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

    public function saveStore(Request $request, $id)
    {
        try {
            $model = Rule::where('uniacid', $this->uniacid())->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $idArray = $request->storeIds ?? [];
            foreach ($idArray as $key => $storeId) {
                $storeModel = Store::where("uniacid", $this->uniacid())->where('storeId', $storeId)->first();
                if (empty($storeModel)) {
                    $storeModel = new Store();
                    $storeModel->storeId = $storeId;
                    $storeModel->ruleId = $model->id;
                }
                $storeModel->fill($model->toArray());
                $storeModel->save();
            }
            return $this->success([], '同步成功');
        } catch (\Exception $e) {
            return $this->failed('同步失败');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            Rule::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->delete();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
