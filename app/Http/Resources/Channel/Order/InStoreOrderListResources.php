<?php

namespace App\Http\Resources\Channel\Order;

use App\Enums\PayEnum;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class InStoreOrderListResources extends ResourceCollection
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
              
                if ($data['diningType'] == 4 && empty($data['prentOrderSn'])) {
                    $data['goods'] = $data['subGoods'];
                }
                unset($data['subGoods']);
                return $data;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
