<?php

namespace App\Http\Resources\ChannelApi\User;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RoomBIllResources extends ResourceCollection
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
            'list' => $this->collection->map(function ($item) {
                return [
                    'id'                    =>  $item->id,
                    'name'              =>  $item->name,
                    'account' => $item->me->account,
                    'final' => $item->me->account > 0 ? '胜' : '负',
                    'desc' =>  rtrim('和' . $item->user->reduce(function ($str, $user) {
                        if ($user->userI != auth('user')->user()->id && $user->type == 0) {
                            $str .= $user->user->nickname . "({$user->totalGrade()})" . ', ';
                        }
                        return $str;
                    }), ", "),
                    'created_at' => $item->created_at->format('m-d H:i'),
                ];
            })
        ];
    }
}
