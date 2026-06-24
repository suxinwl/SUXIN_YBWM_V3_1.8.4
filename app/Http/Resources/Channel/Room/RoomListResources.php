<?php

namespace App\Http\Resources\Channel\Room;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class RoomListResources extends ResourceCollection
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
                $data = $item->toArray();
                $data['roomAdmin']['nickname'] = $item->adminUser->nickname;
                $data['roomAdmin']['avatar'] = $item->adminUser->avatar;
                $data['userCount'] = $item->user->count() - 1;
                $data['serverTime'] = ceil((time() - strtotime($data['created_at'])) / 60);
                $data['created_at']            =  $item->created_at->format('Y-m-d H:i:s');
                $data['updated_at']           =  $item->updated_at->format('Y-m-d H:i:s');
                $data['end_time']           =  !empty($this->end_time) ? $item->end_time->format('Y-m-d H:i:s') : NULL;
                return $data;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
