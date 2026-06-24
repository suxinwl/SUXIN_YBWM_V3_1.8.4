<?php

namespace App\Http\Controllers\Channel\Store;

use App\Enums\SceneEnum;
use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Store\StoreGoodsRequest;
use App\Http\Resources\ChannelApi\Goods\GoodsList as GoodsGoodsList;
use App\Models\GoodsSku;
use App\Models\GoodsSpu;
use App\Models\Order\OrderGoods;
use App\Models\Recipe\RecipeGoods;
use App\Models\Recipe\RecipeStore;
use App\Models\Store;
use App\Models\Store\BaseStoreGoods;
use App\Models\Store\GoodsList;
use App\Models\Store\StoreCategory;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class GoodsController extends ApiController
{

    public function recipe(Request $request)
    {
        $model = RecipeStore::with([
            'store', 'recipe'
        ])->where("storeId", $this->storeId())->first();
        if (empty($model)) {
            $model = new RecipeStore(['storeId' => $this->storeId(), 'recipe' => 0]);
            $model->load(['store', 'recipe']);
        }
        return $this->success($model);
    }


    public  function category(Request $request, $type)
    {
        try {
            $id = $this->storeId();
            $store = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($store)) {
                return $this->success([]);
            }
            if ($type == 1) {
                $ids = collect($store->takeoutCats)->pluck('catId')->all();
            } elseif ($type == 2) {
                $ids = collect($store->inStoreCats)->pluck('catId')->all();
            }
            $ids = $ids ?? [];
            $goodsCount = StoreGoods::where('uniacid', $this->uniacid())
                ->when($request->state, function ($q) use ($request, $type, $id) {
                    if ($request->state == 'offShelf') {
                        return $q->onlyTrashed();
                    }
                    if ($request->state == 'inventoryOff') {
                        return $q->inventoryOff($type, $id);
                    }
                })
                ->where('storeId', $id)->count();
            $list = StoreCategory::whereIn('id', $ids)->withCount(['goodsCat' => function ($q) use ($request, $type, $id) {
                return $q->where('storeId', $id)
                    ->when($request->state, function ($q) use ($request, $type, $id) {
                        if ($request->state == 'offShelf') {
                            return $q->onlyTrashed();
                        }
                        if ($request->state == 'inventoryOff') {
                            return $q->inventoryOff($type, $id);
                        }
                    });
            }])->having('goods_cat_count', '>', 0)
                ->orderBy('sort', 'asc')
                ->orderBy('id', 'desc')
                ->paginate($request->pageSize ?? 20, '*', 'pageNo');
            $list = collect($list)->toArray();
            $list['allCount'] = $goodsCount;
            return $this->success($list);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function index(Request $request, $type)
    {
        $id = $this->storeId();
        $store = Store::where('uniacid', $this->uniacid())->find($id);
        if (empty($store)) {
            return $this->success([]);
        }
        $list = StoreGoods::with('category')->where('uniacid', $this->uniacid())
            ->where('storeId', $id)
            ->when($request->state, function ($q) use ($request, $type, $id) {
                if ($request->state == 'offShelf') {
                    return $q->onlyTrashed();
                }
                if ($request->state == 'inventoryOff') {
                    return $q->inventoryOff($type, $id);
                }
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
            ->when($request->catId, function ($q) use ($request) {
                return $q->whereHas('category', function ($q) use ($request) {
                    return $q->where('catId', $request->catId);
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
//        $duplicates = Store\StoreGoods::withTrashed()
//            ->groupBy('storeId','spuId','recipeId')->havingRaw('count(*) > 1')->get();
//        if($duplicates->toArray()){
//            foreach ($duplicates as $duplicate) {
//                $duplicate->forceDelete();
//            }
//        }
//        $duplicates = Store\StoreGoodsSku::withTrashed()
//            ->groupBy('storeId','spuId','recipeId','specMd5')->havingRaw('count(*) > 1')->get();
//        if($duplicates->toArray()){
//            foreach ($duplicates as $duplicate) {
//                $duplicate->forceDelete();
//            }
//        }
        return $this->success($list);
    }




    public function update(StoreGoodsRequest $request, $type)
    {
        try {
            $id = $this->storeId();
            $models = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($models)) {
                throw new BadRequestException('门店不存在');
            }
            foreach ($request->changes as $key => $v) {
                if (!isset($v['surplusInventory']) || $v['surplusInventory'] < 0) {
                    break;
                }
                $model = StoreGoodsSku::withTrashed()
                    ->where('storeId', $id)->whereNull('deleted_at')
                    ->where('specMd5', $v['specMd5'])
                    ->first();
                if ($model) {
                    $model->inventory = $v['inventory'];
                    $model->dayFilling = $v['dayFilling'];
                    $model->surplusInventory = $v['surplusInventory'];
                    $model->inStoreInventory = $v['inStoreInventory'];
                    $model->save();
                } else {
                    $sku = GoodsSku::where('specMd5', $v['specMd5'])->whereNull('deleted_at')->first();
                    $storeSkuList[] = [
                        'uniacid' => $sku->uniacid,
                        'recipeId' => $models->recipeId,
                        'storeId' => $models->storeId,
                        'specMd5' => $v->specMd5,
                        'inventory' => $v->inventory,
                        'surplusInventory' => $v->inventory,
                        'state' => 1,
                        'spuId' => $sku->spuId,
                        'price' => $sku->price,
                        'sort' => 0,
                        'deleted_at' => null
                    ];
                }
            }
            return $this->success([], '库存修改成功');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    //下架
    public function destroy(Request $request, $type)
    {
        try {
            $id = $this->storeId();
            $models = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($models)) {
                throw new BadRequestException('门店不存在');
            }
            $idArray = $request->goodsIds;
            $list = BaseStoreGoods::where('uniacid', $models->uniacid)
                ->where('storeId', $models->id)
                ->whereIn('spuId', $idArray)
                ->get();
            foreach ($list as $key => $model) {
                $model->delete();
                \App\Models\GoodsLog::setLog($this->uniacid(),$id,$this->userId(),$model->spuId,'',4);
            }
            return $this->success([], '成功');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

//上架
    public function restore(Request $request, $type)
    {
        try {
            $id = $this->storeId();
            $models = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($models)) {
                throw new BadRequestException('门店不存在');
            }
            $idArray = $request->goodsIds;
            $list = BaseStoreGoods::onlyTrashed()
                ->where('uniacid', $models->uniacid)
                ->where('storeId', $models->id)
                ->whereIn('spuId', $idArray)
                ->get();
            foreach ($list as $key => $model) {
                $model->restore();
            }
            return $this->success([], '成功');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function outofStock(Request $request, $type)
    {
        try {
            if (empty($request->goodsIds)) {
                throw new BadRequestException('请选择需要沽清的商品');
            }
            $id = $this->storeId();
            $models = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($models)) {
                throw new BadRequestException('门店不存在');
            }
            $idArray = $request->goodsIds;
            $list = StoreGoodsSku::withTrashed()
                ->where('uniacid', $models->uniacid)
                ->where('storeId', $models->id)
                ->whereIn('spuId', $idArray)
                ->update(['surplusInventory' => 0]);
            return $this->success([], '商品已沽清');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function fillUp(Request $request, $type)
    {
        try {
            if (empty($request->goodsIds)) {
                throw new BadRequestException('请选择需要置满的商品');
            }
            $id = $this->storeId();
            $models = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($models)) {
                throw new BadRequestException('门店不存在');
            }
            $idArray = $request->goodsIds;
            $list = StoreGoodsSku::withTrashed()
                ->where('uniacid', $models->uniacid)
                ->where('storeId', $models->id)
                ->whereIn('spuId', $idArray)
                ->update(['surplusInventory' =>  DB::raw('inventory + 0')]);
            return $this->success([], '成功');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function refreshCache(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $storeId = $id;
        if($storeId){
//            $duplicates = $duplicates = Store\StoreGoodsSku::withTrashed()->where('storeId',$storeId)
//                ->groupBy('storeId','spuId','recipeId','specMd5')->havingRaw('count(*) > 1')->get();
//            if($duplicates){
//                foreach ($duplicates as $duplicate) {
//                    $duplicate->forceDelete(); // 删除除了第一条记录之外的所有记录
//                }
//            }

//            StoreGoods::where('storeId',$storeId)->forceDelete();
//            StoreGoodsSku::where('storeId',$storeId)->forceDelete();
//            RecipeStore::where('storeId',$storeId)->forceDelete();
        }
        return $this->success([], '成功');
    }

    public function count(Request $request, $id)
    {
        //StoreGoodsSku::select(DB::raw())->where()->where('uniacid',$this->uniacid())->groupBy('spuId')->first();
    }

    public function newIndex(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $list = GoodsSpu::where("uniacid", $this->uniacid())
            ->withMax('skus', 'price')
            ->withMin('skus', 'price')
            ->whereHas('storeGoods', function ($q) use ($uniacid, $id) {
                return $q->where('uniacid', $uniacid)->where('storeId', $id);
            })->get();
        return $this->success($list, '成功');
    }
}
