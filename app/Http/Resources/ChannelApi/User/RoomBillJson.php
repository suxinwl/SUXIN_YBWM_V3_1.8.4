<?php

namespace App\Http\Resources\ChannelApi\User;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomBillJson extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                    =>  $this->id,
            'name'              =>  $this->name,
            'account' => $this->me->account,
            'final' => $this->me->account > 0 ? '胜' : '负',
            'created_at' => $this->created_at->format('m-d H:i'),
        ];
    }
}
