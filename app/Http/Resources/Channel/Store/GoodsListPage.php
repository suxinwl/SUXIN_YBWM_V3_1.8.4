<?php

namespace App\Http\Resources\Channel\Store;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class GoodsListPage extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'list' => $this->collection->map(function ($goods) {
                $goods = collect($goods)->toArray();
                $goods['sales'] = $goods['initialSales'] + $goods['sales'];
                if ($goods['specSwitch'] == 1) {
                    $goods['price']  = collect($goods['skus'])->min('price');
                    $goods['discountMinPrice'] = collect($goods['skus'])->min('discount.price');
                    $goods['goodsInventory']  = collect($goods['skus'])->sum('surplusInventory');
                    $goods['mixPrice']  = collect($goods['skus'])->min('price');
                    $goods['maxPrice']  = collect($goods['skus'])->max('price');
                    $goods['discounts'] = collect($goods['skus'])->pluck('discount')->filter(function ($item, $key) {
                        return $item['type'] > 6;
                    })->unique('type')->values();
                } else {
                    $goods['price']  = $goods['singleSpec']['price'];
                    $goods['specMd5'] = $goods['singleSpec']['specMd5'];
                    $goods['discountMinPrice'] =  collect([])->push($goods['singleSpec'])->min('discount.price');
                    $goods['goodsInventory']  = collect([])->push($goods['singleSpec'])->sum('surplusInventory');
                    $goods['discounts'] =  collect([])->push($goods['singleSpec'])->pluck('discount')->filter(function ($item, $key) {
                        return $item['type'] > 6;
                    })->values();
                }
                $goodsCategory = $goods['category'];
                unset($goods['category'], $goods['skus'], $goods['singleSpec']);
                return $goods;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
