<?php

namespace App\Http\Resources\ChannelApi\Goods;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GoodsResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($cat) {
            return [
                "id" => $cat->id,
                "sort" => $cat->sort,
                'label' => $cat->label,
                'name' => $cat->name,
                'logo' => $cat->logo,
                'isMust' => $cat->isMust,
                'notes' => $cat->notes,
                'goodsList' => new GoodsList($cat->goodsList)
            ];
        });
    }
}
