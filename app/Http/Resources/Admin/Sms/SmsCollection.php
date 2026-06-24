<?php

namespace App\Http\Resources\Admin\Sms;


use Illuminate\Http\Resources\Json\ResourceCollection;

class SmsCollection extends ResourceCollection
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
                $temp = $item->toArray();
                $temp['applyName'] = $item->uniacid == 0 ? '总后台' : $item->apply->applyName;
                return $temp;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
