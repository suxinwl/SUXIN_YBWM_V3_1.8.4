<?php

namespace App\Http\Controllers\Channel\Recipe;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Recipe\RecipeGoodsRequest;
use App\Models\GoodsCat;
use App\Models\GoodsSku;
use App\Models\Recipe\RecipeCategory;
use App\Models\Recipe\InstoreGoods;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeGoods;
use App\Models\Recipe\RecipeGoodsBase;
use App\Models\Recipe\RecipeGoodsSku;
use App\Models\Recipe\RecipeSpu;
use App\Models\Recipe\TakeoutGoods;
use App\Models\Recipe\TakeoutSku;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Services\GoodsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class GoodsController extends ApiController
{


    public  function category(Request $request, $id, $type)
    {
        try {
            $recipe = Recipe::where('uniacid', $this->uniacid())->find($id);
            if (empty($recipe)) {
                throw new BadRequestException('数据不存在');
            }
            if ($type == 1) {
                $ids = collect($recipe->takeoutCats)->pluck('catId')->all();
            } elseif ($type == 2) {
                $ids = collect($recipe->inStoreCats)->pluck('catId')->all();
            }
            $ids = $ids ?? [];
            $list = RecipeCategory::whereIn('id', $ids)->withCount(['goodsCat' => function ($q) use ($type, $id) {
                return $q->where('recipeId', $id)->withTrashed();
            }])
                ->orderBy('sort', 'asc')
                ->orderBy('id', 'desc')
                ->paginate($request->pageSize ?? 20, '*', 'pageNo');
            return $this->success($list);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function index(Request $request, $id, $type)
    {
        $recipe = Recipe::where('uniacid', $this->uniacid())->find($id);
        if (empty($recipe)) {
            throw new BadRequestException('数据不存在');
        }
        $list = RecipeGoods::where('uniacid', $this->uniacid())
            ->where('recipeId', $id)
            ->when($request->catId, function ($q) use ($request) {
                return $q->whereHas('category', function ($q) use ($request) {
                    return $q->where('catId', $request->catId);
                });
            })
            ->when($request->channelIds, function ($q) use ($request) {
                if ($request->channelIds == 3) {
                    return $q->whereHas('spu', function ($q) use ($request) {
                        return $q->where('channelIds', 'like', "%1,2%");
                    });
                }
                return $q->whereHas('channel', function ($q) use ($request) {
                    return $q->where('channelId', $request->channelIds);
                });
            })
            ->when($request->name, function ($q) use ($request) {
                return $q->whereHas('spu', function ($q) use ($request) {
                    return $q->where('name', 'like', "%$request->name%");
                });
            })
            ->orderByWith('spu', 'sort', 'asc')
            ->orderByWith('spu', 'spuId', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }



    public function store(Request $request, $id, $type)
    {
        $lock_key = 'recipeGoodsAdd' . $id;
        $is_lock  = Cache::lock($lock_key, 2);
        if (!$is_lock) { // 获取锁权限
            // 防止死锁
            throw new BadRequestException('有商品正在添加中,请稍后再试');
        }
        try {
            $recipe = Recipe::where('uniacid', $this->uniacid())->find($id);
            if (empty($recipe)) {
                throw new BadRequestException('数据不存在');
            }
            GoodsService::recipeGoodsAdd($id, $type, $request->goodsIds);
            optional($is_lock)->release();
            return $this->success();
        } catch (\Exception $e) {
            return $this->failed('失败');
        } finally {
            optional($is_lock)->release();
        }
    }

    public function update(RecipeGoodsRequest $request, $id, $type)
    {
        try {
            $models = Recipe::where('uniacid', $this->uniacid())->find($id);
            if (empty($models)) {
                throw new BadRequestException('当前模板不存在');
            }
            foreach ($request->changes as $key => $v) {
                if ($v['price'] < 0) {
                    break;
                }
                $model = RecipeGoodsSku::withTrashed()
                    ->where('recipeId', $id)
                    ->where('specMd5', $v['specMd5'])
                    ->first();
                $spuId = $model->spuId;
                $selfPriceSwitch = $v['selfPriceSwitch'] ?? 0;
                $selfInventorySwitch = $v['selfInventorySwitch'] ?? 0;
                if ($model) {
                    $model->price = $v['price'];
                    $model->selfPriceSwitch = $selfPriceSwitch ?? 0;
                    $model->selfInventorySwitch = $selfInventorySwitch ?? 0;
                    $model->inStorePrice = $v['inStorePrice'];
                    $model->save();
                    StoreGoodsSku::withTrashed()
                        ->where('recipeId', $id)
                        ->where('specMd5', $v['specMd5'])
                        ->update([
                            'price' => $v['price'],
                            'inStorePrice' => $model->inStorePrice,
                            'selfPriceSwitch' => $model->selfPriceSwitch,
                            'selfInventorySwitch' => $model->selfInventorySwitch
                        ]);
                }
            }
            RecipeGoods::where('recipeId', $id)
                ->where('spuId', $spuId)
                ->update([
                    'selfPriceSwitch' => $selfPriceSwitch,
                    "selfInventorySwitch" => $selfInventorySwitch
                ]);
            StoreGoods::where('recipeId', $id)
                ->where('spuId', $spuId)
                ->update([
                    'selfPriceSwitch' => $selfPriceSwitch,
                    "selfInventorySwitch" => $selfInventorySwitch
                ]);
            return $this->success([], '成功');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function destroy(Request $request, $id, $type)
    {
        try {
            $models = Recipe::where('uniacid', $this->uniacid())->find($id);
            if (empty($models)) {
                throw new BadRequestException('当前模板不存在');
            }
            $idArray = $request->goodsIds;
            $list = RecipeGoodsBase::withTrashed()
                ->where('uniacid', $models->uniacid)
                ->where('recipeId', $models->id)
                ->whereIn('spuId', $idArray)
                ->get();
            foreach ($list as $key => $model) {
                $model->forceDelete();
            }
            return $this->success([], '成功');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }
}
