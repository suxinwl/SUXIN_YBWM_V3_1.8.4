<?php

namespace App\Http\Controllers\Channel\Recipe;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Recipe\Category;
use App\Models\Recipe\InstoreGoods;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeSpu;
use App\Models\Recipe\RecipeStore;
use App\Models\Recipe\TakeoutGoods;
use App\Models\Recipe\TakeoutSku;
use Illuminate\Http\Request;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StoreController extends ApiController
{
    public function index(Request $request, $id)
    {
        $model = Recipe::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $list = RecipeStore::where('recipeId', $id)
            ->where('uniacid', $this->uniacid())
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request, $id)
    {
        $model = Recipe::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $ids = collect($model->store)->pluck('storeId')->all();
        $storeIds = $request->storeIds ?? [];
        $ids = array_diff($storeIds, array_intersect($storeIds, $ids));
        foreach ($ids as $key => $store) {
            $data[] = new RecipeStore([
                'uniacid' => $this->uniacid(),
                'recipeId' => $model->id,
                'storeId' => $store
            ]);
        }

        $model->store()->saveMany($data);
        //StoreGoods::whereIn('storeId',$ids)->where('recipeId','<>',$id)->forceDelete();
        //StoreGoodsSku::whereIn('storeId',$ids)->where('recipeId','<>',$id)->forceDelete();
        return $this->success();
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = $request->storeIds ??[];
            $model = Recipe::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            if ($idArray) {
                $models = RecipeStore::where('uniacid', $this->uniacid())->whereIn('storeId', $idArray)->get();
                foreach ($models as $key => $model) {
                    $model->delete();
                }
            }
            //StoreGoods::where('recipeId',$model->id)->forceDelete();
            //StoreGoodsSku::where('recipeId',$model->id)->forceDelete();
            return $this->success([], '成功');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }
}
