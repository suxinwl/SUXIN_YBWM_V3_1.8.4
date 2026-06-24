<?php

namespace App\Http\Resources\ChannelApi\Goods;

use App\Models\GoodsCat;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class GoodsListPage extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'list' => $this->collection->map(function ($item) {
                $goods =  $item->goods;
                $fither = collect($goods['category'])->filter(function ($category) use ($goods) {
                    $category = GoodsCat::find($category['id']);
                    return $category->inTime && $goods['inTime'];
                });
                return $fither->toArray() ? $goods : null;
            })->filter(function ($item) {
                return !empty($item);
            })->values(),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
