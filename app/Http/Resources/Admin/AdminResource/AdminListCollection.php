<?php

namespace App\Http\Resources\Admin\AdminResource;


use Illuminate\Http\Resources\Json\ResourceCollection;

class AdminListCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($stores = false)
    {
        return [
            'list' => $this->collection->map(function ($item) use ($stores) {
                if ($item->id == 1) {
                    $username =  $item->username . "(超级管理员)";
                } else {
                    $username = empty($item->nickname) ? $item->username : $item->username . "({$item->nickname})";
                }
                return [
                    'id'                    =>  $item->id,
                    'username'              =>  $username,
                    'nickname'              =>  $item->nickname,
                    'role_id'               =>  $item->role_id,
                    'mobile'                =>  $item->mobile,
                    'group_id'              =>  $item->group_id,
                    'group_name'            =>  $item->group->title,
                    'apply_count'           =>  $item->adminApply->count(),
                    'channel'               =>  $item->channel,
                    'roleAppList'               =>      $item->role ? $item->role->appList : [],
                    'role'                  =>  $item->role ? $item->role->module : 'admin',
                    'role_name'             =>  $item->role ? $item->role->name : '超级管理员',
                    'avatar'                =>  $item->avatar,
                    'ip'                    =>  $item->ip,
                    'isAdmin'                =>  $item->isAdmin,
                    'status'                =>  $item->status,
                    'createStoreNum'        => $item->createStoreNum,
                    'login_time'            =>  $item->login_time,
                    'last_login_time'       =>  $item->last_login_time,
                    'created_at'            =>  $item->created_at->format('Y-m-d H:i:s'),
                    'updated_at'            =>  $item->updated_at->format('Y-m-d H:i:s'),
                    'operator' => $item->operator,
                    'stores' => $stores ? $item->stores : []
                ];
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
