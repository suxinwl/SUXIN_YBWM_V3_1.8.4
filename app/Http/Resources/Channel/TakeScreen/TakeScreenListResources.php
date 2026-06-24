<?php

namespace App\Http\Resources\Channel\TakeScreen;

use App\Models\Order\OrderIndex;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class TakeScreenListResources extends ResourceCollection
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
                $order = OrderIndex::where("orderSn", $item->orderSn)->first();
                $data = collect($item)->toArray();
                $data['orderIndex']=  $order ? $order->subOrder : $order;
                return $data;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
