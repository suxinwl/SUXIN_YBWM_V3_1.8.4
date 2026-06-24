<?php

namespace App\Http\Controllers\Channel;
use App\Models\Recipe\RecipeStore;
use App\Http\Controllers\Controller;
use App\Http\Requests\Goods\GoodsRequest;
use App\Http\Requests\MenusRequest;
use App\Http\Resources\Channel\Menus\Menus;
use App\Models\Goods\Channel;
use App\Models\Goods\SetmealGoods;
use App\Models\Goods\SetmealGoodsIds;
use App\Models\Goods\SkuSpecValueIds;
use App\Models\Goods\SpuAttrIds;
use App\Models\Goods\SpuAttrValueIds;
use App\Models\Goods\SpuMaterialIds;
use App\Models\Goods\SpuMaterialValueIds;
use App\Models\Goods\SpuSpecIds;
use App\Models\Goods\SpuSpecValueIds;
use App\Models\GoodsCat;
use App\Models\GoodsCatLabel;
use App\Models\GoodsContent;
use App\Models\GoodsSearch\GoodsSpuBase;
use App\Models\GoodsSku;
use App\Models\GoodsSpu;
use App\Models\Menu;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeGoods;
use App\Models\Recipe\RecipeGoodsSku;
use App\Models\RoleMenu;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Models\StoreGroup;
use App\Services\ExcelService;
use App\Services\GoodsService;
use Illuminate\Http\Request;
use App\Services\MenuService;
use App\Services\OrderService;
use App\Traits\HelperTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

use function PHPSTORM_META\map;

class GoodsController extends ApiController
{

    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $list = GoodsSpu::withCount(['storeGoods as storeCount'])
            ->withMax('skus', 'price')->withMin('skus', 'price')
            ->where('uniacid', $this->uniacid())
//            ->when($request->storeStash, function ($q) use ($request, $storeId) {
//                return $q->where('storeId', ">", 0)->when($storeId, function ($q) use ($storeId) {
//                    return  $q->where('storeId', $storeId);
//                });
//            })
            ->when($request->storeStash, function ($q) use ($request, $storeId) {
                $q->where('storeId', $this->storeId());
            })
            ->when($storeId, function ($q) use ($storeId) {
                return  $q->where('storeId', $storeId);
            })
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == "shelf") {
                    $q->shelf();
                }
                if ($request->state == "offShelf") {
                    $q->offShelf();
                }
                if ($request->state == 'recycle') {
                    $q->onlyTrashed();
                }
                return $q;
            })
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type ?? 1);
            })
            ->when($request->channelIds, function ($q) use ($request) {
                if ($request->channelIds == 3) {
                    return $q->where('channelIds', 'like', "%1,2%");
                }
                return $q->whereHas('channel', function ($q) use ($request) {
                    return $q->where('channelId', $request->channelIds);
                });
            })
            ->when($request->recipeId, function ($q) use ($request) {
                return $q->shelf()->whereDoesntHave('recipeGoods', function ($q) use ($request) {
                    return $q->where('recipeId', $request->recipeId)->where('type', $request->recipeType);
                });
            })
            ->when($request->recommendId, function ($q) use ($request) {
                return $q->shelf()->whereDoesntHave('recommendGoods', function ($q) use ($request) {
                    return $q->where('recommendId', $request->recommendId)->where('type', $request->recommendType);
                });
            })
            ->when($request->catId, function ($q) use ($request) {
                return $q->whereHas('category', function ($q) use ($request) {
                    return $q->where('catId', $request->catId);
                });
            })
            ->when($request->name, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->name%");
            })->when($request->barcode, function ($q) use ($request) {
                return $q->whereHas('skus', function ($q) use ($request) {
                    return $q->where('barcode', 'like',  "%$request->barcode%");
                })->orWhereHas('singleSpec', function ($q) use ($request) {
                    return $q->where('barcode', 'like', "%$request->barcode%");
                });
            })->when($request->sn, function ($q) use ($request) {
                return $q->whereHas('skus', function ($q) use ($request) {
                    return $q->where('sn', 'like', "%$request->sn%");
                })->orWhereHas('singleSpec', function ($q) use ($request) {
                    return $q->where('sn', 'like', "%$request->sn%");
                });
            })->when($request->ids, function ($q) use ($request) {
                return $q->whereIn('id', explode(',', $request->ids));
            })
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc');
        if ($request->export) {
            $list = $list->where('specSwitch', 0)->where('type', 1)->get();
            $header = [
                ['排序', 'sort', 'text'],
                ['商品名称', 'name', 'text'], // 规则不填默认text
                ['商品简介', 'desc', 'text'],
                ['商品分类', 'category', 'function', function ($model) {
                    return collect($model['category'])->implode('name', ',');
                }],
                ['商品标签', 'label', 'function', function ($model) {
                    return collect($model['label'])->implode('name', ',');
                }],
                ['商品角标', 'make.name', 'text'],
                ['拼音助记码', 'pinYin', 'text'],
                ['初始销量', 'initialSales', 'text'],
                ['商品图片', 'logo', 'text'],
                ['商品单位', 'unit.name', 'text'],
                ['销售价格（元）', 'singleSpec.price', 'text'],
                ['成本价（元）', 'singleSpec.costPrice', 'text'],
                ['包装费（元）', 'singleSpec.boxMoney', 'text'],
                ['初始库存', 'singleSpec.inventory', 'text'],
                ['商品条码', 'singleSpec.barcode', 'text'],
                ['商品编码', 'singleSpec.barcode', 'sn'],
                ['售卖渠道', 'channelIds', 'function', function ($model) {
                    return collect($model['channelIds'])->map(function ($channel) {
                        if ($channel == 1) {
                            return "外卖";
                        } elseif ($channel == 2) {
                            return "店内";
                        } else {
                            return '';
                        }
                    })->implode(',');
                }],
                ['商品状态', 'state', 'function', function ($model) {
                    if ($model['state'] == 1) {
                        return "上架";
                    } elseif ($model['state'] == 0) {
                        return "下架";
                    } else {
                        return '';
                    }
                }],
                ['商品详情图', 'images', 'function', function ($model) {
                    return collect($model['images'])->implode(',');
                }],
            ];

            return ExcelService::export($list, $header, '商品.xls');
        }
        $list = $list->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function show(Request $request, $id)
    {
        $model = GoodsSpu::with(['content', 'specs'])->where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }

    public function store(GoodsRequest $request)
    {
        try {
            $uniacid=$this->uniacid();
            $model = new GoodsSpu();
            $model->fill($request->all());
            $model->uniacid = $uniacid;
            $model->storeId = $this->storeId();
            $model->sales = 0;
            $model->save();
            $model->category()->sync($model->catId ?? [], ['uniacid', $uniacid]);
            $model->label()->sync($model->labelId ?? [], ['uniacid', $uniacid]);



            if ($model->storeId > 0) {
                $recipeStore=RecipeStore::where('storeId',$model->storeId)->first();
                if (empty($recipeStore)) {
                    $storeInfo=Store::where('id',$model->storeId)->first();
                    $recipeModel =Recipe::create([
                        'uniacid'=>$uniacid,
                        'name'=>$storeInfo->name,
                    ]);
                    RecipeStore::create([
                        'uniacid'=>$uniacid,
                        'recipeId'=>$recipeModel->id,
                        'storeId'=>$model->storeId,
                    ]);
                    $recipeStore=RecipeStore::where('storeId',$model->storeId)->first();
                }
                RecipeGoods::create([
                    "uniacid" => $uniacid,
                    'recipeId' =>$recipeStore->recipeId,
                    'spuId' => $model->id,
                    'state' => 1,
                    'type' => 1,
                ]);
                StoreGoods::create([
                    "uniacid" => $uniacid,
                    'recipeId' => $recipeStore->recipeId,
                    'storeId' => $model->storeId,
                    'spuId' => $model->id,
                    'state' => 1,
                    'type' => 1,
                ]);
            }
            if (!$model->specSwitch) {
                $singleSpec = new GoodsSku();
                $singleSpec->fill($request->singleSpec);
                $singleSpec->type = 1;
                $singleSpec->spuId = $model->id;
                $singleSpec->uniacid = $uniacid;
                $singleSpec->specMd5 = md5($model->id . 'spec:' . $model->specSwitch);
                $singleSpec->save();
                if ($model->storeId > 0) {
                    $recipeStore=RecipeStore::where('storeId',$model->storeId)->first();
                    if ($recipeStore->recipeId) {
                        RecipeGoodsSku::create([
                            "uniacid" => $uniacid,
                            'recipeId' => $recipeStore->recipeId,
                            'spuId' => $singleSpec->spuId,
                            'specMd5' => $singleSpec->specMd5,
                            'inventory' => $singleSpec->inventory,
                            'price' => $singleSpec->price,
                            'state' => 1,
                            'type' => 1,
                        ]);
                        StoreGoodsSku::create([
                            "uniacid" => $uniacid,
                            'recipeId' => $recipeStore->recipeId,
                            'storeId' => $model->storeId,
                            'spuId' => $singleSpec->spuId,
                            'specMd5' => $singleSpec->specMd5,
                            'inventory' => $singleSpec->inventory,
                            'price' => $singleSpec->price,
                            'state' => 1,
                            'type' => $singleSpec->type,
                            'sort' => 0,
                            'surplusInventory' => $singleSpec->inventory,
                            'dayFilling' => $singleSpec->dayFilling,
                        ]);
                    }


                }
            }
            if (!$model->setMealSwitch) {
                if ($request->setmealData['fix']) {
                    foreach ($request->setmealData['fix'] as $key => $fix) {
                        $fix['uniacid'] = $uniacid;
                        $fix['spuId'] = $model->id;
                        $fixModel = SetmealGoods::create($fix);
                        if ($fix['goods']) {
                            foreach ($fix['goods'] as $key => $fixGoods) {
                                unset($fixGoods['attrData'], $fixGoods['attrs'], $fixGoods['materas'], $fixGoods['materialData'], $fixGoods['isSpec']);
                                $fixGoods['uniacid'] = $uniacid;
                                $fixGoods['goodsId'] = $model->id;
                                $fixGoods['setmealGoodsId'] = $fixModel->id;
                                SetmealGoodsIds::create($fixGoods);
                            }
                            //SetmealGoodsIds::insert($fix['goods']);
                        }
                    }
                }
                if ($request->setmealData['match']) {
                    foreach ($request->setmealData['match'] as $key => $match) {
                        $match['uniacid'] = $uniacid;
                        $match['spuId'] = $model->id;
                        $matchModel = SetmealGoods::create($match);
                        if ($match['goods']) {
                            foreach ($match['goods'] as $key => $matchGoods) {
                                unset($matchGoods['attrData'], $matchGoods['attrs'], $matchGoods['materas'], $matchGoods['materialData'], $matchGoods['isSpec']);
                                $matchGoods['uniacid'] = $uniacid;
                                $matchGoods['goodsId'] = $model->id;
                                $matchGoods['setmealGoodsId'] = $matchModel->id;
                                SetmealGoodsIds::create($matchGoods);
                            }
                        }
                    }
                }
            }

            $model->content()->create([
                'content' => $request->content ?? '',
                'uniacid' => $uniacid
            ]);
            if ($model->channelIds) {
                foreach ($model->channelIds as $key => $id) {
                    Channel::create([
                        'uniacid' => $uniacid,
                        'spuId' => $model->id,
                        'channelId' => $id
                    ]);
                }
            }
            if ($model->specSwitch) {
                foreach ($request->specData as $key => $v) {
                    $spuSpec = SpuSpecIds::create(['specId' => $v['id'], 'spuId' => $model->id, 'uniacid' => $uniacid]);
                    foreach ($v['value'] as $key => $v2) {
                        SpuSpecValueIds::create(
                            [
                                'valueId' => $v2['id'],
                                'specId' => $v['id'],
                                'spuId' => $model->id,
                                'uniacid' => $uniacid,
                                'checkId' => $v2['checkId'] ?? 0
                            ]
                        );
                    }
                }
                foreach ($request->skus as $key => $sku) {
                    $ids = collect($sku['specName'])->sortBy('id')->pluck('id')->all();
                    $sku = GoodsSku::create(array_merge($sku, [
                        'uniacid' => $uniacid,
                        'type' => 2,
                        'spuId' => $model->id,
                        'specMd5' => md5(implode(',', $ids) . $model->id)
                    ]));
                    foreach ($ids as $key => $valueId) {
                        SkuSpecValueIds::create([
                            'uniacid' => $uniacid,
                            'spuId' => $model->id,
                            'skuId' => $sku->id,
                            'valueId' => $valueId
                        ]);
                    }
                    if ($model->storeId > 0) {
                        $recipeStore=RecipeStore::where('storeId',$model->storeId)->first();
                        if ($recipeStore->recipeId) {
                            RecipeGoodsSku::create([
                                "uniacid" => $uniacid,
                                'recipeId' => $recipeStore->recipeId,
                                'spuId' => $sku->spuId,
                                'specMd5' => $sku->specMd5,
                                'inventory' => $sku->inventory,
                                'price' => $sku->price,
                                'state' => 1,
                                'type' => $sku->type,
                            ]);
                            StoreGoodsSku::create([
                                "uniacid" => $uniacid,
                                'recipeId' => $recipeStore->recipeId,
                                'storeId' => $model->storeId,
                                'spuId' => $sku->spuId,
                                'specMd5' => $sku->specMd5,
                                'inventory' => $sku->inventory,
                                'price' => $sku->price,
                                'state' => 1,
                                'type' => $sku->type,
                                'sort' => 0,
                                'surplusInventory' => $sku->inventory,
                                'dayFilling' => $sku->dayFilling,
                            ]);
                        }

                    }
                }
            }
            if ($model->attrSwitch) {
                foreach ($request->attrData as $key => $v) {
                    $spuSpec = SpuAttrIds::create(['attrId' => $v['id'], 'spuId' => $model->id, 'uniacid' => $uniacid, 'state' => $v['state'] ?? 0]);
                    foreach ($v['value'] as $key => $v2) {
                        SpuAttrValueIds::create(['checkId' => $v2['checkId'] ?? 0, 'valueId' => $v2['id'], 'attrId' => $v['id'], 'spuId' => $model->id, 'uniacid' => $this->uniacid()]);
                    }
                }
            }
            if ($model->materialSwitch) {
                foreach ($request->materialData as $key => $v) {
                    $spuSpec = SpuMaterialIds::create([
                        'materialId' => $v['id'],
                        'spuId' => $model->id,
                        'uniacid' => $uniacid,
                        'required' => $v['required'] ?? 0,
                        'maxNum' => $v['maxNum'] ?? 0,
                        'astrict' => $v['astrict'] ?? 0,
                    ]);
                    foreach ($v['materialList'] as $key => $v2) {
                        SpuMaterialValueIds::create(['checkId' => $v2['checkId'] ?? 0, 'valueId' => $v2['id'], 'materialId' => $v['id'], 'spuId' => $model->id, 'uniacid' => $this->uniacid()]);
                    }
                }
            }
            \App\Models\GoodsLog::setLog($uniacid,$this->storeId(),$this->userId(),$model->id,'',$type=1);
            return $this->success([], '操作成功');
        } catch (\Exception $e) {
            // DB::rollBack();
            return $this->failed($e->getMessage(). '-' . $e->getLine() . $e->getFile());
        }
    }


    public function update(GoodsRequest $request, $id)
    {
        try {
            $redisKey = 'updateGoods:' . $id;
            if (Redis::setNx($redisKey, true)) {
                Redis::expire($redisKey, 120);
            } else {
                throw new BadRequestException('该商品数据已有更新,请重试');
            }
            DB::beginTransaction();
            $model = GoodsSpu::where('uniacid', $this->uniacid())->find($id);
            if (!$model) {
                throw new BadRequestException('商品不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->push();
            $model->category()->sync($model->catId ?? [], ['uniacid', $model->uniacid]);
            $model->label()->sync($model->labelId ?? [], ['uniacid', $model->uniacid]);

            GoodsContent::updateOrcreate([
                'uniacid' => $this->uniacid(),
                'spuId' => $model->id,
            ], [
                'uniacid' => $this->uniacid(),
                'spuId' => $model->id,
                'content' => $request->content ?? '',
            ]);
            //商品售卖渠道
            Channel::where('uniacid', $this->uniacid())->where('spuId', $model->id)->delete();
            if ($model->channelIds) {
                foreach ($model->channelIds as $key => $id) {
                    Channel::create([
                        'uniacid' => $this->uniacid(),
                        'spuId' => $model->id,
                        'channelId' => $id
                    ]);
                }
            }
            //套餐内容
            if (!$model->setMealSwitch) {
                $ids = collect($model->setMeal)->pluck('id')->all();
                SetmealGoods::where('uniacid', $this->uniacid())->whereIn('id', $ids)->delete();
                SetmealGoodsIds::where('uniacid', $this->uniacid())->whereIn('setmealGoodsId', $ids)->delete();
                if ($request->setmealData['fix']) {
                    foreach ($request->setmealData['fix'] as $key => $fix) {
                        $fix['uniacid'] = $this->uniacid();
                        $fix['spuId'] = $model->id;
                        $fixModel = SetmealGoods::create($fix);
                        if ($fix['goods']) {
                            foreach ($fix['goods'] as $key => $fixGoods) {
                                unset($fixGoods['attrData'], $fixGoods['attrs'], $fixGoods['materas'], $fixGoods['materialData'], $fixGoods['isSpec']);
                                $fixGoods['uniacid'] = $this->uniacid();
                                $fixGoods['goodsId'] = $model->id;
                                $fixGoods['setmealGoodsId'] = $fixModel->id;
                                SetmealGoodsIds::create($fixGoods);
                            }
                        }
                    }
                }
                if ($request->setmealData['match']) {
                    foreach ($request->setmealData['match'] as $key => $match) {
                        $match['uniacid'] = $this->uniacid();
                        $match['spuId'] = $model->id;
                        $matchModel = SetmealGoods::create($match);
                        if ($match['goods']) {
                            foreach ($match['goods'] as $key => $matchGoods) {
                                unset($matchGoods['attrData'], $matchGoods['attrs'], $matchGoods['materas'], $matchGoods['materialData'], $matchGoods['isSpec']);
                                $matchGoods['uniacid'] = $this->uniacid();
                                $matchGoods['goodsId'] = $model->id;
                                $matchGoods['setmealGoodsId'] = $matchModel->id;
                                SetmealGoodsIds::create($matchGoods);
                            }
                        }
                    }
                }
            }

            //单规格
            GoodsSku::where('uniacid', $this->uniacid())->where('spuId', $model->id)->forceDelete();
            if (!$model->specSwitch) {
                $singleSpec = new GoodsSku();
                $singleSpec->fill($request->singleSpec);
                $singleSpec->type = 1;
                $singleSpec->spuId = $model->id;
                $singleSpec->uniacid = $model->uniacid;
                $singleSpec->specMd5 = md5($model->id . 'spec:' . $model->specSwitch);
                $singleSpec->save();
                if ($model->storeId > 0) {
                    $recipeStore = RecipeStore::where('storeId', $model->storeId)->first();
                    if ($recipeStore->recipeId) {
                        StoreGoods::updateOrCreate([
                            "uniacid" => $model->uniacid,
                            'recipeId' => $recipeStore->recipeId,
                            'storeId' => $model->storeId,
                            'spuId' => $model->id,
                        ], [
                            "uniacid" => $model->uniacid,
                            'recipeId' => $recipeStore->recipeId,
                            'storeId' => $model->storeId,
                            'spuId' => $model->id,
                            'state' => 1,
                            'type' => 1
                        ]);
                        StoreGoodsSku::updateOrCreate([
                            "uniacid" => $model->uniacid,
                            'recipeId' => $recipeStore->recipeId,
                            'storeId' => $model->storeId,
                            'spuId' => $singleSpec->spuId,
                            'specMd5' => $singleSpec->specMd5,
                        ], [
                            "uniacid" => $model->uniacid,
                            'recipeId' => $recipeStore->recipeId,
                            'storeId' => $model->storeId,
                            'spuId' => $singleSpec->spuId,
                            'specMd5' => $singleSpec->specMd5,
                            'inventory' => $singleSpec->inventory,
                            'price' => $singleSpec->price,
                            'state' => 1,
                            'type' => $singleSpec->type,
                            'sort' => 0,
                            'surplusInventory' => $singleSpec->inventory,
                            'dayFilling' => $singleSpec->dayFilling,
                        ]);
                    }
                }
            }
            //属性
            SpuAttrIds::where('uniacid', $this->uniacid())->where('spuId', $model->id)->delete();
            SpuAttrValueIds::where('uniacid', $this->uniacid())->where('spuId', $model->id)->delete();
            if ($model->attrSwitch) {
                foreach ($request->attrData as $key => $v) {
                    if ($v['value']) {
                        SpuAttrIds::create(['attrId' => $v['id'], 'spuId' => $model->id, 'uniacid' => $this->uniacid(), 'state' => $v['state'] ?? 0]);
                        foreach ($v['value'] as $key => $v2) {
                            SpuAttrValueIds::create([
                                'checkId' => $v2['checkId'] ?? 0,
                                'valueId' => $v2['id'],
                                'attrId' => $v['id'],
                                'spuId' => $model->id,
                                'uniacid' => $this->uniacid()
                            ]);
                        }
                    }
                }
            }
            //多规格
            if ($model->specSwitch) {
                SpuSpecIds::where('uniacid', $this->uniacid())->where('spuId', $model->id)->delete();
                SpuSpecValueIds::where('uniacid', $this->uniacid())->where('spuId', $model->id)->delete();
                foreach ($request->specData as $key => $v) {
                    SpuSpecIds::create(['specId' => $v['id'], 'spuId' => $model->id, 'uniacid' => $this->uniacid()]);
                    foreach ($v['value'] as $key => $v2) {
                        SpuSpecValueIds::create(
                            [
                                'valueId' => $v2['id'],
                                'specId' => $v['id'],
                                'spuId' => $model->id,
                                'uniacid' => $this->uniacid(),
                                'checkId' => $v2['checkId'] ?? 0
                            ]
                        );
                    }
                }
                StoreGoodsSku::where('spuId',$model->id)->where('storeId',$model->storeId)->forceDelete();
                foreach ($request->skus as $key => $sku) {
                    $skuMd5='';
                    $ids = collect($sku['specName'])->sortBy('id')->pluck('id')->all();
                    $skuMd5=md5(implode(',', $ids) . "goodsID:" . $model->id);

                    $sku = GoodsSku::create(array_merge($sku, [
                        'uniacid' => $model->uniacid,
                        'type' => 2,
                        'spuId' => $model->id,
                        'specMd5' => $skuMd5
                    ]));
                    foreach ($ids as $key => $valueId) {
                        SkuSpecValueIds::create([
                            'uniacid' => $this->uniacid(),
                            'spuId' => $model->id,
                            'skuId' => $sku->id,
                            'valueId' => $valueId
                        ]);
                    }
                    if ($model->storeId > 0) {

                        $recipeStore=RecipeStore::where('storeId',$model->storeId)->first();
                        if ($recipeStore->recipeId) {
                            StoreGoods::updateOrCreate([
                                "uniacid" => $model->uniacid,
                                'recipeId' => $recipeStore->recipeId,
                                'storeId' => $model->storeId,
                                'spuId' => $sku->spuId,
                            ], [
                                "uniacid" => $model->uniacid,
                                'recipeId' => $recipeStore->recipeId,
                                'storeId' => $model->storeId,
                                'spuId' => $sku->spuId,
                                'state' => 1,
                                'type' => 1
                            ]);



                            StoreGoodsSku::Create([
                                "uniacid" => $model->uniacid,
                                'recipeId' => $recipeStore->recipeId,
                                'storeId' => $model->storeId,
                                'spuId' => $sku->spuId,
                                'specMd5' => $skuMd5,
                                'inventory' => $sku->inventory,
                                'price' => $sku->price,
                                'state' => 1,
                                'type' => $sku->type,
                                'sort' => 0,
                                'surplusInventory' => $sku->inventory,
                                'dayFilling' => $sku->dayFilling,
                            ]);
                        }else{
                            DB::rollBack();
                        }
                    }
                }
            }

            //加料
            SpuMaterialIds::where('uniacid', $this->uniacid())->where('spuId', $model->id)->delete();
            SpuMaterialValueIds::where('uniacid', $this->uniacid())->where('spuId', $model->id)->delete();
            if ($model->materialSwitch) {
                foreach ($request->materialData as $key => $v) {
                    SpuMaterialIds::create([
                        'materialId' => $v['id'],
                        'spuId' => $model->id,
                        'uniacid' => $this->uniacid(),
                        'required' => $v['required'] ?? 0,
                        'maxNum' => $v['maxNum'] ?? 0,
                        'astrict' => $v['astrict'] ?? 0,
                    ]);
                    foreach ($v['materialList'] as $key => $v2) {
                        SpuMaterialValueIds::create([
                            'checkId' => $v2['checkId'] ?? 0,
                            'valueId' => $v2['id'],
                            'materialId' => $v['id'],
                            'spuId' => $model->id,
                            'uniacid' => $this->uniacid()
                        ]);
                    }
                }
            }
            Redis::del($redisKey);
            if ($model->storeId == 0) {
                GoodsService::sync($model->id);
            }
            \App\Models\GoodsLog::setLog($this->uniacid(),$this->storeId(),$this->userId(),$model->id,'',2);
            DB::commit();
            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            DB::rollBack();
            Redis::del($redisKey);
            return $this->failed($e->getMessage());
        }
    }


    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            DB::beginTransaction();
            $ids = RecipeGoods::where('uniacid', $this->uniacid())->where('uniacid', $this->uniacid())->whereIn('spuId', $idArray)->first();
            if ($ids) {
                return $this->failed("“{$ids->recipe->name}” " . '模板正在使用该商品，无法删除');
            }
            $models = GoodsSpu::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                if ($model->storeId > 0) {
                    $recipeStore = RecipeStore::where('storeId', $model->storeId)->first();
                    if ($recipeStore->recipeId) {
                        StoreGoods::where('uniacid', $this->uniacid())
                            ->where('spuId', $model->id)
                            ->where('recipeId', $recipeStore->recipeId)
                            ->update([
                                'deleted_at' => Carbon::now()->toDateTimeString()
                            ]);
                        StoreGoodsSku::where('uniacid', $this->uniacid())
                            ->where('spuId', $model->id)
                            ->where('recipeId', $recipeStore->recipeId)
                            ->update([
                                'deleted_at' => Carbon::now()->toDateTimeString()
                            ]);
                    }

                }
                $model->delete();
            }
            GoodsSku::where('uniacid', $this->uniacid())->whereIn('spuId', $idArray)->delete();
            foreach ($idArray as $v){
                \App\Models\GoodsLog::setLog($this->uniacid(),$this->storeId(),$this->userId(),$v,'',5);
            }

            DB::commit();
            return $this->success([], '成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed('失败');
        }
    }

    public function restore(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            DB::beginTransaction();
            $models = GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                if ($model->storeId > 0) {
                    $recipeStore = RecipeStore::where('storeId', $model->storeId)->first();
                    if ($recipeStore->recipeId) {
                        StoreGoods::where('uniacid', $this->uniacid())
                            ->where('spuId', $model->id)
                            ->where('recipeId', $recipeStore->recipeId)
                            ->update([
                                'deleted_at' => null
                            ]);
                        StoreGoodsSku::where('uniacid', $this->uniacid())
                            ->where('spuId', $model->id)
                            ->where('recipeId', $recipeStore->recipeId)
                            ->update([
                                'deleted_at' => null
                            ]);
                    }

                }
                $model->restore();
            }
            // GoodsSku::where('uniacid', $this->uniacid())->whereIn('spuId', $idArray)->restore();
            foreach ($idArray as $v){
                \App\Models\GoodsLog::setLog($this->uniacid(),$this->storeId(),$this->userId(),$v,'',6);
            }
            DB::commit();
            return $this->success([], '恢复成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed('恢复失败');
        }
    }

    public function forceDelete(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            DB::beginTransaction();
            $ids = RecipeGoods::where('uniacid', $this->uniacid())->where('uniacid', $this->uniacid())->whereIn('spuId', $idArray)->first();
            if ($ids) {
                return $this->failed("“{$ids->recipe->name}”" . '模板正在使用该商品，无法删除');
            }
            $models = GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                SpuSpecIds::where('spuId', $model->id)->delete();

                if ($model->storeId > 0) {
                    $recipeStore = RecipeStore::where('storeId', $model->storeId)->first();
                    if ($recipeStore->recipeId) {
                        DB::table('store_goods')->where('uniacid', $this->uniacid())
                            ->where('spuId', $model->id)
                            ->where('recipeId', $recipeStore->recipeId)
                            ->delete();
                        DB::table('store_goods_sku')->where('uniacid', $this->uniacid())
                            ->where('spuId', $model->id)
                            ->where('recipeId', $recipeStore->recipeId)
                            ->delete();
                    }

                }

                $model->forceDelete();
            }
            foreach ($idArray as $v){
                \App\Models\GoodsLog::setLog($this->uniacid(),$this->storeId(),$this->userId(),$v,'',7);
            }
            DB::commit();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed('删除失败');
        }
    }

    public function state(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                if ($request->type == 'shelf') {
                    $model->state = 1;
                } elseif ($request->type == 'offShelf') {
                    $model->state = 0;
                } else {
                    $model->state = $model->state == 1 ? 0 : 1;
                }
                $model->save();
                if($model->state==1){
                    \App\Models\GoodsLog::setLog($this->uniacid(),$this->storeId(),$this->userId(),$model->id,'',3);
                }else{
                    \App\Models\GoodsLog::setLog($this->uniacid(),$this->storeId(),$this->userId(),$model->id,'',4);
                }

                if ($model->storeId > 0) {
                    StoreGoods::where('uniacid', $this->uniacid())
                        ->where('spuId', $model->id)
                        ->where('recipeId', 0)
                        ->update([
                            'deleted_at' => $model->state == 1 ? null : Carbon::now()->toDateTimeString()
                        ]);
                    StoreGoodsSku::where('uniacid', $this->uniacid())
                        ->where('spuId', $model->id)
                        ->where('recipeId', 0)
                        ->update([
                            'deleted_at' => $model->state == 1 ? null : Carbon::now()->toDateTimeString()
                        ]);
                }
            }

            DB::commit();
            return $this->success([], '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed('操作失败');
        }
    }

    public function count(Request $request)
    {
        $storeId = $this->storeId();
        $list = GoodsSpu::stateCount()->where('uniacid', $this->uniacid())
            ->when($request->storeStash, function ($q) use ($request, $storeId) {
                return $q->where('storeId', ">", 0)->when($storeId, function ($q) use ($storeId) {
                    return  $q->where('storeId', $storeId);
                });
            })->when(!$request->storeStash, function ($q) use ($request, $storeId) {
                $q->where('storeId', $this->storeId());
            })
            ->where('type', $request->type)
            ->first();
        $list = $list->makeHidden(['specData', 'attrData', 'materialData', 'inTime', 'setmealData', 'discounts', 'content', 'isSpec', '', 'skus', 'singleSpec', 'category', 'label', 'unit', 'mark']);
        return $this->success($list);
    }


    public function batch(Request $request, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        switch ($request->type) {
            case "shelf":
                GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->update(['state' => $request->value]);
                break;
                break;
            case "boxMoney":
                GoodsSku::withTrashed()->where('uniacid', $this->uniacid())->whereIn('spuId', $idArray)->update(['boxMoney' => $request->value]);
                break;
            case "desc":
                GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->update(['desc' => $request->value]);
                break;
            case "unit":
                GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->update(['unitId' => $request->value]);
                break;
            case "oneDelivery":
                GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->update(['oneDeliverySwitch' => $request->value]);
                break;
            case "initialSales":
                GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->update(['initialSales' => $request->value]);
                break;
            case "markId":
                GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->update(['markId' => $request->value]);
                break;
            case "min":
                GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->update(['min' => $request->value]);
                break;
            case "labelId":
                $goodsList = GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
                foreach ($goodsList as $key => $goods) {
                    $goods->labelId = $request->value;
                    $goods->save();
                    $goods->label()->sync($goods->labelId ?? [], ['uniacid', $goods->uniacid]);
                }
                break;
            case "category":
                $goodsList = GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
                foreach ($goodsList as $key => $goods) {
                    $goods->catId = $request->value;
                    $goods->save();
                    $goods->category()->sync($goods->catId ?? [], ['uniacid', $goods->uniacid]);
                }
                break;
            case "inventory":
                GoodsSku::withTrashed()->where('uniacid', $this->uniacid())->whereIn('spuId', $idArray)->update(['inventory' => $request->value]);
                break;
            case "channelIds":
                $goodsList = GoodsSpu::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
                foreach ($goodsList as $key => $goods) {
                    $goods->channelIds = $request->value;
                    $goods->save();
                    Channel::where('uniacid', $this->uniacid())->where('spuId', $goods->id)->delete();
                    foreach ($request->value as $key => $id) {
                        Channel::create([
                            'uniacid' => $this->uniacid(),
                            'spuId' => $goods->id,
                            'channelId' => $id
                        ]);
                    }
                }
                break;
        }
        return  $this->success();
    }

    public function sku(Request $request)
    {
        $type = $request->type ?? 1;
        $storeId = $this->storeId();
        $list = GoodsSku::with([
            'spu' => function ($q) {
                return $q->select(['name', 'id', 'logo']);
            },
            "specName"
        ])->where('uniacid', $this->uniacid())
            ->whereHas('spu', function ($q) use ($request, $type, $storeId) {
                return $q->where('state', 1)
                    //->where('storeId', $storeId)
                    ->when($type, function ($q) use ($type) {
                        return  $q->where('type', $type);
                    })->when($request->name, function ($q) use ($request) {
                        return  $q->where('name', 'like', "%$request->name%");
                    })->when($request->channelIds, function ($q) use ($request) {
                        if ($request->channelIds == 3) {
                            return $q->where('channelIds', 'like', "%1,2%");
                        }
                        return $q->whereHas('channel', function ($q) use ($request) {
                            return $q->where('channelId', $request->channelIds);
                        });
                    });
            })
            ->when($request->catId, function ($q) use ($request) {
                return $q->whereHas('category', function ($q) use ($request) {
                    return $q->where('catId', $request->catId);
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }
}
