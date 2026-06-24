<?php

namespace App\Http\Controllers\Channel\Recipe;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Recipe\Category;
use App\Models\Recipe\InstoreGoods;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeGoods;
use App\Models\Recipe\RecipeGoodsSku;
use App\Models\Recipe\RecipeSpu;
use App\Models\Recipe\RecipeStore;
use App\Models\Recipe\TakeoutGoods;
use App\Models\Recipe\TakeoutSku;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class RecipeController extends ApiController
{
    public function index(Request $request)
    {
        $list = Recipe::withCount(['store as storeCount', 'takeoutGoods as takeoutGoodsCount'])->where('uniacid', $this->uniacid())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->keyword%");
            })
            ->orderBy('sort', 'asc')->orderBy('id', 'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
//        $duplicates = RecipeGoods::withTrashed()
//            ->groupBy('spuId','recipeId')->havingRaw('count(*) > 1')->get();
//
//        if($duplicates->toArray()){
//            foreach ($duplicates as $duplicate) {
//                $duplicate->forceDelete();
//            }
//        }
//        $duplicates = RecipeGoodsSku::withTrashed()
//            ->groupBy('spuId','recipeId','specMd5')->havingRaw('count(*) > 1')->get();
//        if($duplicates->toArray()) {
//            foreach ($duplicates as $duplicate) {
//                RecipeGoodsSku::where('id', $duplicate->id)->forceDelete(); // 删除除了第一条记录之外的所有记录
//
//            }
//        }
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $model = new Recipe();
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->save();
        return $this->success();
    }

    public function show(Request $request, $id)
    {
        $model = Recipe::withCount(['store as storeCount', 'takeoutGoods as takeoutGoodsCount'])->where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }

    public function update(Request $request, $id)
    {
        $model = Recipe::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->save();
        return $this->success();
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = Recipe::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            RecipeGoods::withTrashed()->whereIn('recipeId', $idArray)->forceDelete();
            RecipeGoodsSku::withTrashed()->whereIn('recipeId', $idArray)->forceDelete();
            RecipeStore::whereIn('recipeId', $idArray)->forceDelete();
            StoreGoods::withTrashed()->whereIn('recipeId', $idArray)->forceDelete();
            StoreGoodsSku::withTrashed()->whereIn('recipeId', $idArray)->forceDelete();
            return $this->success([], '成功');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function copy(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $model = Recipe::with(['goods', 'goodsSku'])->where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $recipe = $model->replicate();
            $recipe->name = $recipe->name . "_复制";
            $recipe->save();
            foreach ($model->goods as $key => $goods) {
                $recGoods = new RecipeGoods($goods->toArray());
                $recGoods->recipeId = $recipe->id;
                $recGoods->created_at = date("Y-m-d H:i:s", time());
                $recGoods->updated_at = date("Y-m-d H:i:s", time());
                $recGoods->saveQuietly();
            }

            foreach ($model->goodsSku as $key => $goodsSku) {
                $recGoodsSku = new RecipeGoodsSku($goodsSku->toArray());
                $recGoodsSku->recipeId = $recipe->id;
                $recGoodsSku->created_at = date("Y-m-d H:i:s", time());
                $recGoodsSku->updated_at = date("Y-m-d H:i:s", time());
                $recGoodsSku->saveQuietly();
            }
            DB::commit();
            return $this->success([], '成功');
        } catch (\Exception $e) {
            DB::rollBack();
            file_put_contents('Recipe.log',$e->getMessage().PHP_EOL,FILE_APPEND);
            return $this->failed('失败');
        }
    }
}
