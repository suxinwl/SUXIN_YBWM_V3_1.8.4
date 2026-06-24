<?php

namespace App\Http\Resources\Channel\Order;

use App\Enums\PayEnum;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class OrderListResources extends ResourceCollection
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
                $data = $item->toArray();
                $data['roomName'] = $item->room->name;
                $data['payFormat'] = PayEnum::format($item->payMod);
                return $data;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
