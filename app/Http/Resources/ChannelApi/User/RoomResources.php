<?php

namespace App\Http\Resources\ChannelApi\User;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResources extends JsonResource
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
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'users' => $this->user->map(function ($user) {
                $item = $user->toArray();
                $item['nickname'] = $user->nickname;
                $item['avatar'] = $user->avatar;
                $item['account'] = abs($user->account);
                $item['grade_type'] = $user->type == 1 ? 1 : intval($user->account > 0);
                return $item;
            }),
            'logs' => $this->log->map(function ($log) {
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
        ];
    }
}
