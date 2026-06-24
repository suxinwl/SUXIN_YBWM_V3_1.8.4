<?php

namespace App\Http\Resources\Channel\ExchangeCode;

use App\Enums\PayEnum;
use App\Models\ExchangeCode\ExchangeCodeReceive;
use App\Models\ExchangeCode\ExchangeCode;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class ExchangeCodeResources extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $code = ExchangeCode::where('id', $request->exchangeCodeId)->first();
        $num=ExchangeCodeReceive::where('state',2)->where('exchangeCodeId',$request->exchangeCodeId)->count();
        return [
            'list' => $this->collection,
            'sn' => $code ? $code->sn : null,
            'num' => $num,
            'total' => $code->num, // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
