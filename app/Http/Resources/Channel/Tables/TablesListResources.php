<?php

namespace App\Http\Resources\Channel\Tables;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class TablesListResources extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'list' => $this->collection->map(function ($item) {
                $item->setAppends(['diningType','stateFormat','minutes','order']);
                $item->makeHidden(['store']);
                $item = collect($item)->toArray();
                $item['diningType']= 4;
                return $item;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
