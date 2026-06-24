<?php

namespace App\Http\Resources\Admin\Muster;

use App\Models\Setmeal;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MusterListCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $available=intval(Setmeal::where('state',1)->count());
        $offshelf=intval(Setmeal::where('state',2)->count());
        return [
            'list' => $this->collection,
            'total' => $this->total(), // 数据总数
            'available'=>$available,
            'offshelf'=>$offshelf,
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
