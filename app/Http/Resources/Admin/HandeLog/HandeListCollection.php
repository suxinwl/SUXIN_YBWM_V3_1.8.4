<?php

namespace App\Http\Resources\Admin\HandeLog;

use App\Enums\AdminRoutEnum;
use Illuminate\Http\Resources\Json\ResourceCollection;

class HandeListCollection extends ResourceCollection
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
                return [
                    'id' => $item->id,
                    'route' => AdminRoutEnum::format($item->route),
                    'username' => empty($item->admin->nickname) ? $item->admin->username : $item->admin->username . "({$item->admin->nickname})",
                    "ip" => $item->ip,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    "method" => $item->method,
                    "input" => strlen($item->input)>100?mb_substr($item->input, 0, 100, 'utf-8').'......':$item->input,
                ];
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
