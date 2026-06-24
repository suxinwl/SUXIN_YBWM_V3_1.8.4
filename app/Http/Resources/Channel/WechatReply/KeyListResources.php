<?php

namespace App\Http\Resources\Channel\WechatReply;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class KeyListResources extends ResourceCollection
{
    public $data=[];
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
       $this->collection->map(function ($item, $data) {
            $arr = explode(",", $item->keyword);
            if(is_array($arr)){
                $this->data = array_merge($this->data, $arr);
            }
            return $arr;
        });
        return [
            'list' => $this->data,
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
