<?php

namespace App\Http\Resources\ChannelApi\Store;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class StoreList extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'list' => $this->collection->map(function ($item) {
                return $item->setAppends(['distance', 'storeSetting','realtimeState']);
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
