<?php

namespace App\Http\Resources\ChannelApi\Goods;

use App\Models\Store\StoreGoods;
use App\Services\ConfigService;
use DB;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class GoodsList extends ResourceCollection
{
    public $data;
    public $userId;
    public $inTime;
    public $categoryId;
    public function __construct($resource, $data = [], $userId = 0, $categoryId = 0)
    {
        parent::__construct($resource);
        $this->data = $data;
        $this->userId = $userId;
        $this->categoryId = $categoryId;
    }

    public function toArray($request, $data = [], $userId = 0)
    {
        $data = $this->data;
        $userId = $this->userId;
        $categoryId = $this->categoryId;
        $orderConfig = null;
        $list =  $this->collection->filter(function ($goods, $key) use (&$data, $categoryId) {
            return $goods['inTime'];
        })->each(function ($goods, $key) use (&$data, $userId, $categoryId, $orderConfig) {
            $goods = collect($goods)->toArray();
            $sku=$goods['sku']?:$goods['skus'];

            $skuArray=[];
            if($sku){
                $skuArray=array_column($sku,'linePrice');
            }
            if (!$orderConfig) {
                $orderConfig = ConfigService::getChannelConfig('orderSetting', $goods['uniacid']);
            }
            $goods['sales'] = $goods['initialSales'] + $goods['sales'];
            if ($goods['specSwitch'] == 1) {
                $goods['price']  = collect($goods['sku'])->min('price')?:collect($goods['skus'])->min('price');
                $goods['linePrice']  =$skuArray?max($skuArray):'';
                $goods['equityCardPrice'] =  collect($goods['skus'])->where('discount.type', 10)->min('discount.price');
                $goods['goodsInventory']  = collect($goods['skus'])->sum('surplusInventory');$goods['discountMinPrice'] = collect($goods['skus'])->where('discount.type', 6)->min('discount.price');
                if($orderConfig['discountMinPrice']==1) {
                    $goods['vipPrice'] =  collect([])->push($goods['singleSpec'])->where('discount.type', 6)->min('discount.vipPrice');
                }
                $goods['discounts'] = collect($goods['skus'])->pluck('discount')->filter(function ($item, $key) {
                    return $item['type'] > 6 && $item['type'] != 10;
                })->unique('type')->values();
            } else {

                $goods['price']  = $goods['singleSpec']['price'];
                $goods['linePrice']  =$skuArray?max($skuArray):'';
                $goods['specMd5'] = $goods['singleSpec']['specMd5'];
                $goods['discountMinPrice'] = collect([])->push($goods['singleSpec'])->where('discount.type', 6)->min('discount.price');
                if($orderConfig['discountMinPrice']==1) {
                    $goods['vipPrice'] =  collect([])->push($goods['singleSpec'])->where('discount.type', 6)->min('discount.vipPrice');
                }
                $goods['equityCardPrice'] =  collect([])->push($goods['singleSpec'])->where('discount.type', 10)->min('discount.price');
                $goods['goodsInventory']  = collect([])->push($goods['singleSpec'])->sum('surplusInventory');
                $goods['discounts'] =  collect([])->push($goods['singleSpec'])->pluck('discount')->filter(function ($item, $key) {
                    return $item['type'] > 6 && $item['type'] != 10;
                })->values();
            }
            if (!empty($userId)) {
                $userLimitKey = "userGoods:" . $goods['storeId'] . $goods['id'] . ":" . $userId;
                $userBuy = Cache::get($userLimitKey) ?? 0;
                if ($goods['userlimitSwitch'] == 1 && $goods['userLimit'] <= $userBuy) {
                    $goods["isBuy"] = 0;
                }
                $userDayLimitKey = "userGoods:" . $goods['storeId'] . date("Y-m-d") . ":" . $goods['id'] . ":" . $userId;
                $userDayBuy = Cache::get($userDayLimitKey) ?? 0;
                if ($goods['daylimitSwitch'] == 1 && $goods['daylimit'] <= $userDayBuy) {
                    $goods["isBuy"] = 0;
                }
            }
            if ($orderConfig['soldoutShow'] == 1 || $goods['goodsInventory'] > 0) {
                $goodsCategory = $goods['category'];
                unset($goods['category'], $goods['skus'], $goods['singleSpec']);
                $goods["isBuy"] = 1;
                $keyData=array_keys($data);
                foreach ($goodsCategory as $key => $category) {
                    if(in_array($category['id'],$keyData)){
                        if (!isset($data[$category['id']]) || empty($data[$category['id']])) {
                            $data[$category['id']] = collect($category)->toArray();
                        }
                        if ($categoryId == 0) {
                            $data[$category['id']]['goodsList'][] = $goods;
                        } elseif ($category['id'] == $categoryId) {
                            $data[$category['id']]['goodsList'][] = $goods;
                        }
                    }

                }
            }
        });
        if ($categoryId == 0) {
            return  collect($data)->filter(function ($goods, $key) use ($orderConfig) {
                if ($goods['inTime'] == 0) {
                    return false;
                } else {
                    return !empty($goods['goodsList']);
                }
            })->values();
        } else {
            return [
                'list' => collect($data)->values(),
                'total' => $this->total(), // 数据总数
                'pageSize' => $this->perPage(), // 每页数量
                'pageNo' => $this->currentPage(), // 当前页码
            ];
        }
    }
}
