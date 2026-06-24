<?php

namespace App\Http\Controllers\Channel\InStore;

use App\Enums\SceneEnum;
use App\Http\Controllers\Channel\ApiController;
use App\Http\Resources\Channel\Store\GoodsListPage as StoreGoodsListPage;
use App\Http\Resources\ChannelApi\Goods\GoodsList as GoodsGoodsList;
use App\Http\Resources\ChannelApi\Goods\GoodsListPage;
use App\Http\Resources\ChannelApi\Goods\GoodsResource;
use App\Models\GoodsCat;
use App\Models\GoodsSearch\Store\InStoreGoods as StoreInStoreGoods;
use App\Models\InStore\Goods;
use App\Models\StatisticsDay;
use App\Models\Store;
use App\Models\Store\GoodsList;
use App\Models\Store\InStoreGoods;
use App\Models\Store\StoreCategory;
use App\Models\Store\StoreGoods;
use App\Services\ConfigService;
use App\Services\StoreGeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GoodsController extends ApiController
{
    public function index(Request $request)
    {
        try {
            $storeId = $this->storeId();
            $userId = $this->userId();
            $store = Store::where('uniacid', $this->uniacid())->find($storeId);
            if (empty($store)) {
                throw new BadRequestHttpException('门店不存在');
            }
            $uniacid = $this->uniacid();
            $list = StoreInStoreGoods::with(['category', 'skus' => function ($q) use ($uniacid, $storeId) {
                return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
            }, 'singleSpec' => function ($q) use ($uniacid, $storeId) {
                return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
            }, 'label', 'unit', 'mark'])
                ->when($request->name, function ($q) use ($request, $uniacid) {

                    return $q->where(function ($q) use ($request, $uniacid) {
                        return $q->where('name', 'like', "%$request->name%");
                    });

                })
                ->where('uniacid', $this->uniacid())
                ->where('salesType', 1)
                ->when($request->categoryId, function ($q) use ($request) {
                    return $q->whereHas('category', function ($q) use ($request) {
                        return $q->where('catId', $request->categoryId);
                    });
                })
                ->where(function ($q) use ($uniacid, $storeId) {
                    return $q->whereHas('skus', function ($q) use ($uniacid, $storeId) {
                        return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
                    })->orWhere(function ($q) use ($uniacid, $storeId) {
                        return $q->whereHas('singleSpec', function ($q) use ($uniacid, $storeId) {
                            return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
                        });
                    });
                })
                ->whereHas('channel', function ($q) use ($uniacid) {
                    return $q->where('channelId', 2)->where('uniacid', $uniacid);
                })
                ->orderBy('sort', 'asc')
                ->get();
            $ids = collect($store->takeoutCats)->pluck('catId')->all();
            if ($ids) {
                $ids = DB::table('goods_cat')->where('uniacid', $this->uniacid())
                    ->whereIn('id', $ids)
                    ->orderBy('sort', 'asc')
                    ->orderBy('id', 'desc')
                    ->get();
                foreach ($ids as $key => $v) {
                    $data[$v->id] = [];
                }
            }
            // StatisticsDay::where('uniacid', $this->uniacid())->where('day', date("Y-m-d", time()))
            // ->increment('pv', 1);
            return $this->success(new GoodsGoodsList($list, $data, 0));
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
    public function show(Request $request, $id)
    {
        $goods = StoreGoods::with(['spu' => function ($q) {
            return $q->select(['*']);
        }])->where('storeId', $this->storeId())->where('spuId', $id)->first();
        if (empty($goods)) {
            throw new BadRequestHttpException('商品不存在或已下架');
        }
        return $this->success($goods->goods);
    }

    public function update(Request $request, $id)
    {
        try {
            $id = $this->storeId();
            $type = $this->scene();
            $store = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($store)) {
                throw new BadRequestHttpException('门店不存在');
            }
            $key = "storeGoods:" . $id . $type;
            //if (!Cache::has($key)) {
            if (true) {
                $list = GoodsList::where('uniacid', $this->uniacid())->where('storeId', $id)->get();
                $list = collect(new GoodsGoodsList($list))->toArray();
                Cache::set($key, $list, 600);
            } else {
                $list = Cache::get($key);
            }
            $list = collect($list)->filter(function ($goods, $key) use (&$data) {
                return $goods['inTime'];
            })->each(function ($goods, $key) use (&$data) {
                $goodsCategory = $goods['category'];
                unset($goods['category']);
                foreach ($goodsCategory as $key => $category) {
                    if (!isset($data[$category['id']])) {
                        $data[$category['id']] = $category;
                    }
                    $data[$category['id']]['goodsList'][] = $goods;
                }
            });
            $data = collect($data)->filter(function ($goods, $key) use (&$data) {
                if ($goods['inTime'] == 0) {
                    return false;
                } else {
                    return !empty($goods['goodsList']);
                }
            })->values();
            StatisticsDay::where('uniacid', $this->uniacid())->where('day', date("Y-m-d", time()))
                ->increment('pv', 1);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function search(Request $request)
    {
        $uniacid = $this->uniacid();
        $list = GoodsList::with('category')
            ->where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->whereHas('channel', function ($q) use ($uniacid) {
                return $q->where('channelId', 2)->where('uniacid', $uniacid);
            })
            ->when($request->keyword, function ($q) use ($request, $uniacid) {
                return $q->whereHas('spu', function ($q) use ($request, $uniacid) {
                    return $q->where(function ($q) use ($request, $uniacid) {
                        return $q->where('name', 'like', "%$request->keyword%")
                            ->orWhere('barcode', 'like', "%$request->keyword%")
                            ->orWhere('sn', 'like', "%$request->keyword%");
                    })
                        ->where('uniacid', $uniacid);
                });
            })
            ->orderByWith('spu', 'sort', 'asc')
            ->orderByWith('spu', 'id', 'desc')
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success(new GoodsListPage($list));
    }

    public  function category(Request $request)
    {
        try {
            $id = $this->storeId();
            $store = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($store)) {
                return $this->failed("门店不存在");
            }
            $ids = $ids ?? [];
            $goodsCount = StoreGoods::where('uniacid', $this->uniacid())
                ->where('storeId', $id)->count();
            $list = StoreCategory::withCount(['goodsCat' => function ($q) use ($request, $id) {
                return $q->where('storeId', $id);
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

    public function goods(Request $request)
    {
        try {
            $storeId = $this->storeId();
            $userId = $this->userId();
            $store = Store::where('uniacid', $this->uniacid())->find($storeId);
            if (empty($store)) {
                throw new BadRequestHttpException('门店不存在');
            }
            $uniacid = $this->uniacid();
            $list = StoreInStoreGoods::with(['category', 'skus' => function ($q) use ($uniacid, $storeId) {
                return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
            }, 'singleSpec' => function ($q) use ($uniacid, $storeId) {
                return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
            }, 'label', 'unit', 'mark'])
                ->where('uniacid', $this->uniacid())
                ->when($request->categoryId, function ($q) use ($request) {
                    return $q->whereHas('category', function ($q) use ($request) {
                        return $q->where('catId', $request->categoryId);
                    });
                })
                ->when($request->keyword, function ($q) use ($request, $uniacid) {
                    return $q->where(function ($q) use ($request, $uniacid) {
                        return $q->where('name', 'like', "%$request->keyword%")
                            ->orWhere('pinYin', 'like', "%$request->keyword%")
                            ->orWhere(function ($q) use ($request) {
                                return $q->whereHas('goodsSkus', function ($q) use ($request) {
                                    return $q->where('barcode', 'like', "%$request->keyword%")
                                    ->orWhere('sn', 'like', "%$request->keyword%");
                                });
                            });
                    });
                })
                ->where(function ($q) use ($uniacid, $storeId) {
                    return $q->whereHas('skus', function ($q) use ($uniacid, $storeId) {
                        return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
                    })->orWhere(function ($q) use ($uniacid, $storeId) {
                        return $q->whereHas('singleSpec', function ($q) use ($uniacid, $storeId) {
                            return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
                        });
                    });
                })
                ->whereHas('channel', function ($q) use ($uniacid) {
                    return $q->where('channelId', 2)->where('uniacid', $uniacid);
                })
                ->orderBy('sort', 'asc')
                ->paginate($request->pageSize ?? 20, '*', 'pageNo');
            // StatisticsDay::where('uniacid', $this->uniacid())->where('day', date("Y-m-d", time()))
            // ->increment('pv', 1);
            return $this->success(new StoreGoodsListPage($list));
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
