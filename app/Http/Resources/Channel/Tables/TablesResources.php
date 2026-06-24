<?php

namespace App\Http\Resources\Channel\Tables;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class TablesResources extends ResourceCollection
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
                $data['areaName'] = $item->area->name;
                $data['roomName'] = $item->room->name;
                return $data;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
