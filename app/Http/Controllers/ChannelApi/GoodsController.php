<?php

namespace App\Http\Controllers\ChannelApi;

use App\Enums\SceneEnum;
use App\Http\Resources\ChannelApi\Goods\GoodsList as GoodsGoodsList;
use App\Http\Resources\ChannelApi\Goods\GoodsListPage;
use App\Http\Resources\ChannelApi\Goods\GoodsResource;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Jobs\PvJob;
use App\Models\GoodsCat;
use App\Models\GoodsSearch\Store\TakeoutGoods;
use App\Models\GoodsSku;
use App\Models\GoodsSpu;
use App\Models\Partner;
use App\Models\StatisticsDay;
use App\Models\Store;
use App\Models\Store\GoodsList;
use App\Models\Store\StoreCategory;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Services\ConfigService;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\StoreGeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Image;
use App\Models\Admin\Apply;
class GoodsController extends ApiController
{
    public function index(Request $request)
    {
        try {
            $storeId = $this->storeId();
            $type = $this->scene();
            $userId = $this->userId();
            $store = Store::where('uniacid', $this->uniacid())->find($storeId);
            if (empty($store)) {
                throw new BadRequestHttpException('门店不存在');
            }
            $channelId=$request->diningType==30?3:1;
            $time=date('Y-m-d H:i:s',time());
            $uniacid = $this->uniacid();
            $list = TakeoutGoods::with(['sku','category', 'skus' => function ($q) use ($uniacid, $storeId) {
                return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
            }, 'singleSpec' => function ($q) use ($uniacid, $storeId) {
                return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
            }, 'label', 'unit', 'mark'=> function ($q) use ($uniacid,$time) {
                return $q->where('startTime','<',$time)->where('endTime','>', $time)->where('uniacid', $uniacid);
            },])
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
                ->whereHas('channel', function ($q) use ($uniacid,$channelId) {
                    return $q->where('channelId', $channelId)->where('uniacid', $uniacid);
                })
                ->orderBy('sort', 'asc')
                ->orderBy('id', 'desc')
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
            return $this->success(new GoodsGoodsList($list, $data, $userId));
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function inStore(Request $request)
    {
        try {
            $id = $this->storeId();
            $type = $this->scene();
            $uniacid = $this->uniacid();
            $store = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($store)) {
                throw new BadRequestHttpException('门店不存在');
            }
            $key = "inStoreGoods:" . $id;
            //if (!Cache::has($key)) {
            if (true) {
                $list = GoodsList::with('category')
                    ->where('uniacid', $this->uniacid())
                    ->where('storeId', $id)
                    ->whereHas('channel', function ($q) use ($uniacid) {
                        return $q->where('channelId', 2)->where('uniacid', $uniacid);
                    })
                    ->orderByWith('spu', 'sort', 'asc')
                    ->orderByWith('spu', 'id', 'desc')
                    ->get();
                $list = collect(new GoodsGoodsList($list))->toArray();
                Cache::set($key, $list, 600);
            } else {
                $list = Cache::get($key);
            }
            $data = [];
            $ids = collect($store->takeoutCats)->pluck('catId')->all();
            if ($ids) {
                $ids = GoodsCat::where('uniacid', $this->uniacid())
                    ->whereIn('id', $ids)
                    ->orderBy('sort', 'asc')
                    ->orderBy('id', 'desc')
                    ->get();
                foreach ($ids as $key => $v) {
                    $data[$v->id] = [];
                }
            }
            $list = collect($list)->filter(function ($goods, $key) use (&$data) {
                return $goods['inTime'];
            })->each(function ($goods, $key) use (&$data) {
                $goodsCategory = $goods['category'];
                unset($goods['category']);
                foreach ($goodsCategory as $key => $category) {
                    if (!isset($data[$category['id']]) || empty($data[$category['id']])) {
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
            dispatch(new PvJob($this->uniacid()));
            // StatisticsDay::where('uniacid', $this->uniacid())
            //     ->where('day', date("Y-m-d", time()))
            //     ->increment('pv', 1);
            return $this->success($data);
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
        $goods=$goods->goods;

        $dir = public_path('storage' . '/' . $this->uniacid().'/xcx');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            chmod($dir, 0777);
        }
        $file = $goods['id'].'_xcx.png';
        if(!file_exists($dir.'/'.$file)){
            $page='pages/shop/good-dl?goodId='.$goods['id'].'&storeId='.$this->storeId();
            $app = ChannelOpenWechat::miniProgram($this->uniacid());
            $response = $app->app_code->get($page);
            $image = $response->getBody()->getContents();
            file_put_contents($dir.'/'.$file, $image);
        }
        $goods['code']='https://' . $_SERVER['HTTP_HOST'].'/storage/'.$this->uniacid().'/xcx/'.$goods['id'].'_xcx.png';

        $apply=Apply::where('id',$this->uniacid())->first();
        // 加载WebP图像
        $webpImage = $apply->applyImage;
        $extension=pathinfo($webpImage)['extension'];

        if($extension=='webp'){

            $pngImage = public_path('storage/'.$this->uniacid().'/apply/'.$this->uniacid().'.png');
            $dir = public_path('storage' . '/' . $this->uniacid().'/apply');
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
                chmod($dir, 0777);
            }
            if(!file_exists($pngImage)){
                $apply=Apply::where('id',$this->uniacid())->first();
                // 加载WebP图像
                $webpImage = $apply->applyImage;
                $source = imagecreatefromwebp($webpImage);
                if($source){
                    // 创建一个新的真彩色图像资源，尺寸与原图相同
                    $width = imagesx($source);
                    $height = imagesy($source);
                    $target = imagecreatetruecolor($width, $height);

                    // 复制图像内容到新资源中，这一步是必要的，因为直接保存可能导致质量损失或格式问题
                    imagecopy($target, $source, 0, 0, 0, 0, $width, $height);

                    // 保存为PNG格式

                    imagepng($target, $pngImage);

                    // 释放内存
                    imagedestroy($source);
                    imagedestroy($target);
                }

            }
            $domain = 'https://' . Request()->server('HTTP_HOST');
            $goods['applyLogo']=$domain.'/storage/'.$this->uniacid().'/apply/'.$this->uniacid().'.png';
        }else{
            $goods['applyLogo']=$webpImage;
        }



        $webpImage = $goods['logo'];
        $extension=pathinfo($webpImage)['extension'];
        if($extension=='webp'){
            $pngImage = public_path('storage/'.$this->uniacid().'/goods/'.$goods['id'].'.png');
            $dir = public_path('storage' . '/' . $this->uniacid().'/goods');
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
                chmod($dir, 0777);
            }
            if(!file_exists($pngImage)){
                // 加载WebP图像
                $webpImage = $goods['logo'];
                $source = imagecreatefromwebp($webpImage);
                if($source) {
                    // 创建一个新的真彩色图像资源，尺寸与原图相同
                    $width = imagesx($source);
                    $height = imagesy($source);
                    $target = imagecreatetruecolor($width, $height);

                    // 复制图像内容到新资源中，这一步是必要的，因为直接保存可能导致质量损失或格式问题
                    imagecopy($target, $source, 0, 0, 0, 0, $width, $height);

                    // 保存为PNG格式

                    imagepng($target, $pngImage);

                    // 释放内存
                    imagedestroy($source);
                    imagedestroy($target);
                }
            }
            $domain = 'https://' . Request()->server('HTTP_HOST');
            $goods['logo']=$domain.'/storage/'.$this->uniacid().'/goods/'.$goods['id'].'.png';
        }



        return $this->success($goods);
    }

    public function update(Request $request, $id)
    {
        try {
            $storeId = $this->storeId();
            $type = $this->scene();
            $userId = $this->userId();
            $store = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($store)) {
                throw new BadRequestHttpException('门店不存在');
            }
            $key = "storeGoods:" . $id . $type;
            $uniacid = $this->uniacid();
            //if (!Cache::has($key)) {
            $list = TakeoutGoods::with(['category', 'skus' => function ($q) use ($uniacid, $storeId) {
                return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
            }, 'singleSpec' => function ($q) use ($uniacid, $storeId) {
                return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
            }])
                ->where('uniacid', $this->uniacid())
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
                    return $q->where('channelId', 1)->where('uniacid', $uniacid);
                })
                ->orderBy('sort', 'asc')
                ->get();
            return $this->success($list);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function search(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $list = GoodsList::with('category')
            ->where('uniacid', $this->uniacid())
            ->where('storeId', $id)
            ->whereHas('channel', function ($q) use ($uniacid) {
                return $q->where('channelId', 1)->where('uniacid', $uniacid);
            })
            ->when($request->keyword, function ($q) use ($request, $uniacid) {
                return $q->whereHas('spu', function ($q) use ($request, $uniacid) {
                    return $q->where('name', 'like', "%$request->keyword%")->where('uniacid', $uniacid);
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
            $list = StoreCategory::withCount(['goodsCat' => function ($q) use ($request, $id) {
                return $q->where('storeId', $id)->whereHas('channel', function ($q) {
                    return $q->where('channelId', 1);
                });
            }])->having('goods_cat_count', '>', 0)
                ->orderBy('sort', 'asc')
                ->orderBy('id', 'desc')
                ->get();
            $list = collect($list)->filter(function ($cat, $key) {
                if ($cat->inTime == 0) {
                    return false;
                } else {
                    return true;
                }
            })->values();
            return $this->success($list);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public  function goods(Request $request)
    {
        try {
            $storeId = $this->storeId();
            $uniacid = $this->uniacid();
            $userId = $this->userId();
            $store = Store::where('uniacid', $this->uniacid())->find($storeId);
            if (empty($store)) {
                return $this->failed("门店不存在");
            }
            $ids = $ids ?? [];
            $list = TakeoutGoods::with(['category', 'skus' => function ($q) use ($uniacid, $storeId) {
                return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
            }, 'singleSpec' => function ($q) use ($uniacid, $storeId) {
                return $q->where('storeId',  $storeId)->where('uniacid', $uniacid);
            }, 'label', 'unit', 'mark'])
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
                    return $q->where('channelId', 1)->where('uniacid', $uniacid);
                })
                ->orderBy('sort', 'asc')
                ->orderBy('id', 'desc')
                ->paginate(Request()->size ?? 1, '*', 'page');
            return $this->success(new GoodsGoodsList($list, [], $userId, intval($request->categoryId)));
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }


}
