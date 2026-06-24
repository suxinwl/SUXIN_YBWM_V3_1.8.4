<?php

namespace App\Http\Resources\Channel\Order;

use App\Enums\PayEnum;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Http\Resources\Json\JsonResource;

class InStoreOrderResources extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = collect($this)->toArray();
        $data['discounts'] = collect($this->discounts)->groupBy('type')->flatten();
        return $data;
    }
}
