<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeGoods;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Traits\HelperTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class GoodsService
{
    public static function recipeGoodsAdd($recipeId, $type, $goodsIds)
    {
        $recipe = Recipe::find($recipeId);
        if (empty($recipe)) {
            throw new BadRequestException('数据不存在');
        }
        $goodsIds = is_array($goodsIds) ? $goodsIds : [$goodsIds];
        $ids = collect($recipe->goods()->get())->pluck('spuId')->all();
        $ids = array_diff($goodsIds, array_intersect($goodsIds, $ids));
        if ($ids) {
            foreach ($ids as $key => $spuId) {
                $data[] = new RecipeGoods([
                    'uniacid' => $recipe->uniacid,
                    'spuId' => $spuId,
                ]);
            }
            $recipe->goods()->saveMany($data);
        }
        return true;
    }

    public static function recipeGoodsDel($recipeId, $type, $goodsIds)
    {
        try {
            $models = Recipe::find($recipeId);
            if (empty($models)) {
                throw new BadRequestException('当前模板不存在');
            }
            $idArray = is_array($goodsIds) ? $goodsIds : [$goodsIds];
            $list = RecipeGoods::withTrashed()
                ->where('uniacid', $models->uniacid)
                ->where('recipeId', $models->id)
                ->whereIn('spuId', $idArray)
                ->get();
            foreach ($list as $key => $model) {
                $model->forceDelete();
            }
            return true;
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
        }
    }


    public static function sync($goodsIds)
    {
        try {
            DB::beginTransaction();
            $goodsIds = is_array($goodsIds) ? $goodsIds : [$goodsIds];
            $recipeGoods = RecipeGoods::withTrashed()->whereIn('spuId', $goodsIds)->get();
            foreach ($recipeGoods as $key => $v) {
                $a = collect($v->sku)->pluck('specMd5')->all();
                if ($v->spu->specSwitch == 0) {
                    $b = [$v->spu->singleSpec->specMd5];
                } else {
                    $b = collect($v->spu->skus)->pluck('specMd5')->all();
                }
                $diff = collect($b)->diff($a)->all();
                if (!empty($diff)) {
                    self::recipeGoodsDel($v->recipeId, $v->type, $v->spuId);
                    self::recipeGoodsAdd($v->recipeId, $v->type, $v->spuId);
                }
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }
}
