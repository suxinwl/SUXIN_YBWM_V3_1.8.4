<?php

namespace App\Http\Resources\Channel\Goods;

use App\Models\GoodsSku;
use App\Models\GoodsSpu;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class AttrList extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request, $goodsId = 0)
    {
        $goods = GoodsSpu::withTrashed()->select(['attrData'])->first();
        $attrData = empty($godos) ? [] : $goods['attrData'];
        return [
            'list' => $this->collection->map(function ($item) use ($attrData) {
                $attr = $item->toArray();
                $attrData = collect($attrData)->groupBy('id')->toArray();
                $select = isset($attrData[$attr['id']]) ? $attrData[$attr['id']][0] : [];
                if ($select) {
                    $attr['select'] = 1;
                } else {
                    $attr['select'] = 0;
                }
                collect($attr['value'])->map(function ($value, $key) use ($select) {
                    if (in_array($key, $select['checkList'])) {
                        $value['select'] = 1;
                    } else {
                        $value['select'] = 0;
                    }
                    return $value;
                });
                return $item;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
