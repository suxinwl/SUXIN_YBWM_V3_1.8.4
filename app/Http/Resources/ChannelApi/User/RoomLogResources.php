<?php

namespace App\Http\Resources\ChannelApi\User;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RoomLogResources extends ResourceCollection
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
            'list' => $this->collection->map(function ($log) {
                $item = [
                    'id' => $log->id,
                    'roomId' => $log->roomId,
                    'type' => $log->type,
                    'nickname' => $log->user->nickname,
                    'avatar' => $log->user->avatar,
                    'grade' => $log->grade,
                ];
                if ($log->type == 2) {
                    $item['user'] = [
                        'nickname' => $log->user->nickname,
                        'avatar' => $log->user->avatar
                    ];
                    $item['toUser'] = [
                        'nickname' => $log->toUser->nickname,
                        'avatar' => $log->toUser->avatar
                    ];
                }
                if ($log->type == 3) {
                    $item['data'] = $log->data;
                    $item['grade_type'] = $log->grade_type;
                }
                return $item;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
