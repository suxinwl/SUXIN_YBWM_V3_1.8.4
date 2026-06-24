<?php

namespace App\Http\Resources\ChannelApi\Order;

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
                $item->setAppends(['subOrder', 'payTypeFormat', 'payChannelFormat', 'payStateFormat', 'mchId']);
                $order = collect($item)->toArray();
                if ($item->subOrder) {
                    if ($item->type == 4 && empty($item->subOrder->prentOrderSn) && $item->subOrder->diningType == 4) {
                        $order['subOrder']['goods'] = $item->subOrder->subGoods;
                    }
                }
                return $order;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
