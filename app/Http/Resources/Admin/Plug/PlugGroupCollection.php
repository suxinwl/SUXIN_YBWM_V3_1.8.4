<?php

namespace App\Http\Resources\Admin\Plug;


use Illuminate\Http\Resources\Json\ResourceCollection;

class PlugGroupCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [];
        return [
            'list' => $this->collection->whereNotIn('appName',['douyin','miniPlay','dividend','seckill','clubTogether','buyUp'])->groupBy('appType'),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
