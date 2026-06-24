<?php

namespace App\Http\Controllers\ChannelApi\publicMiniProgram;

use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Resources\ChannelApi\Goods\GoodsList as GoodsGoodsList;
use App\Http\Resources\ChannelApi\Store\StoreList;
use App\Models\Admin\Apply;
use App\Models\Collect;
use App\Models\GoodsSearch\Store\TakeoutGoods;
use App\Models\OpenWechatAuth;
use App\Models\Store;
use App\Models\Store\StoreCategory;
use App\Services\ConfigService;
use App\Services\StoreGeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IndexController extends ApiController
{
    /**
     * 获取公域小程序扫码点餐二维码
     */
    public function getUrlScheme(Request $request)
    {
        $uniacid = $this->uniacid();
        if (empty($uniacid)){
            return $this->failed('uniacid不合法！');
        }
        $wechatConfig = OpenWechatAuth::where([
            "uniacid" => $uniacid
        ])->get()[0];
        $accessToken = $this->getAccessToken($wechatConfig->authorizer_access_token, $wechatConfig->open_appid, $wechatConfig->authorizer_appid, $wechatConfig->authorizer_refresh_token);
        return $this->success($accessToken);

        $storeId = Store::where("uniacid", "=", $uniacid)->get()[0]->id;

        // 初始化cURL会话
        $ch = curl_init();

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_URL, "https://api.weixin.qq.com/wxa/generatescheme?access_token=".$accessToken); // 目标URL
        curl_setopt($ch, CURLOPT_POST, true); // 发起POST请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'path' => 'pages/index/goods',
            'query' => http_build_query([
                "uniacid" => $uniacid,
                "storeId" => $storeId
            ])
        ))); // POST参数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 返回响应而不是输出

        // 执行cURL会话
        $response = curl_exec($ch);

        // 关闭cURL会话
        curl_close($ch);

        // 打印响应内容
//        echo $response;
        return $this->success($response);
    }
    /**
     * 获取公域小程序首页轮播图
     */
    public function getSwiper(Request $request)
    {
        return $this->success(array(
            'https://cycdn.hdzk.net/1/uploads/2024/07/04/202407041540175913.jpg',
            'https://cycdn.hdzk.net/1/uploads/2024/07/04/202407041540178079.jpg',
            'https://cycdn.hdzk.net/1/uploads/2024/07/04/202407041540176561.png',
            'https://cycdn.hdzk.net/1/uploads/2024/07/04/202407041540178493.jpg'
        ));
    }
    /**
     * 选择门店获取门店商品类目
     */
    public function getCategory(Request $request)
    {
        try {
            $id = $this->storeId();
            $store = Store::find($id);
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
    /**
     * 选择门店获取门店商品
     */
    public function getStoreGoods(Request $request)
    {
        try {
            $storeId = $this->storeId();
            $type = $this->scene();
            $userId = $this->userId();
            $store = Store::find($storeId);
            if (empty($store)) {
                throw new BadRequestHttpException('门店不存在');
            }
            $list = TakeoutGoods::with(['category', 'skus' => function ($q) use ($storeId) {
                return $q->where('storeId',  $storeId);
            }, 'singleSpec' => function ($q) use ($storeId) {
                return $q->where('storeId',  $storeId);
            }, 'label', 'unit', 'mark'])
                ->where('salesType', 1)
                ->when($request->categoryId, function ($q) use ($request) {
                    return $q->whereHas('category', function ($q) use ($request) {
                        return $q->where('catId', $request->categoryId);
                    });
                })
                ->where(function ($q) use ($storeId) {
                    return $q->whereHas('skus', function ($q) use ($storeId) {
                        return $q->where('storeId',  $storeId);
                    })->orWhere(function ($q) use ($storeId) {
                        return $q->whereHas('singleSpec', function ($q) use ($storeId) {
                            return $q->where('storeId',  $storeId);
                        });
                    });
                })
                ->whereHas('channel', function ($q) {
                    return $q->where('channelId', 1);
                })
                ->orderBy('sort', 'asc')
                ->orderBy('id', 'desc')
                ->get();
            $ids = collect($store->takeoutCats)->pluck('catId')->all();
            if ($ids) {
                $ids = DB::table('goods_cat')
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
    /*
     * 获取公域门店
     * */
    public function getPublicStore(Request $request)
    {
        $store_list = Store::all();
        return  $this->success($store_list);
    }
    /*
     * 获取公域品牌
     * */
    public function getPublicBrand(Request $request)
    {
        $brand_list = Apply::where('status', '=', 1)->get();
        return  $this->success($brand_list);
    }
    public function getStore(Request $request)
    {
        $res = array();
        $brand_list = Apply::where('status', '=', 1)->get();
        foreach ($brand_list as $item) {
            $store = $this->getStores($request, $item['id']);
            $list = json_decode(json_encode($store))->list;
            if (sizeof($list) != 0){
                array_push($res, $store);
            }
        }
        return $this->success($res);
    }
    private function getStores(Request $request, $uniacid)
    {
        $config = ConfigService::getChannelConfig('storeBasicSetting', $uniacid);
        if ($config['pageState'] == 1 && empty($request->recharge)) {
            $km = $config['km'];
        } else {
            $km = 15000;
        }
        $storeIds  = StoreGeoService::getRadius($uniacid, $request->lat, $request->lng, $km, 'km', ["ASC"]);
        if ($request->collect) {
            $collectList  = Collect::select(["collectId"])->where("uniacid", $uniacid)
                ->where("type", 'store')
                ->where('userId', $this->userId())
                ->get();
            if (empty($collectList)) {
                return $this->success([]);
            }
            $collectList = collect($collectList)->pluck('collectId')->all();
            $storeIds  = array_intersect($storeIds, $collectList);
        }
        if ($request->searchIds) {
            $ids = is_array($request->searchIds) ? $request->searchIds : explode(',', $request->searchIds);
            $storeIds  = array_intersect($storeIds, $ids);
        }
        if ($request->filtrIds) {
            $ids = is_array($request->filtrIds) ? $request->filtrIds : explode(',', $request->filtrIds);
            $storeIds  = array_diff($storeIds, $ids);
        }
        $list = Store::business()
            ->with(['collectStore', 'label'])->whereIn('id', $storeIds)
            ->when($request->regionId, function ($q) use ($request) {
                return $q->where('region', 'like', "%$request->regionId%");
            })
            ->when($request->cityId &&  $request->cityId != 'undefined' && $config['pageState'] == 2, function ($q) use ($request) {
                return $q->where('region', 'like', "%$request->cityId%");
            })
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->keyword%");
            })
            ->where('isolate',0)
            ->orderByRaw("FIELD(`id`," . implode(',', $storeIds) . ")")
            ->paginate($request->size ?? 10, '*', 'page');
        return new StoreList($list);
//        return $this->success(new StoreList($list));
    }
    private function getAccessToken($component_access_token, $component_appid, $authorizer_appid, $authorizer_refresh_token){
        // 初始化cURL会话
        $ch = curl_init();

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_URL, "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=".$component_access_token); // 目标URL
        curl_setopt($ch, CURLOPT_POST, true); // 发起POST请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'component_appid' => $component_appid,
            'authorizer_appid' => $authorizer_appid,
            'authorizer_refresh_token' => $authorizer_refresh_token
        ))); // POST参数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 返回响应而不是输出

        // 执行cURL会话
        $response = curl_exec($ch);

        // 关闭cURL会话
        curl_close($ch);

        // 打印响应内容
//        echo $response;
        return $response;
    }
}
