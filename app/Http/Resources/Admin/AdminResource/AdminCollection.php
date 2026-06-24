<?php

namespace App\Http\Resources\Admin\AdminResource;

use App\Models\Admin;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AdminCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = auth('admin')->user();
        return [
            'list' => $this->collection->map(function ($item) {
                return [
                    'id'                    =>  $item->id,
                    'username'              =>  $item->username,
                    'nickname'              =>  $item->nickname,
                    'role_id'               =>  $item->role_id,
                    'mobile'                =>  $item->mobile,
                    'group_id'              =>  $item->group_id,
                    'group_name'            =>  $item->group->title,
                    'apply_count'           =>  $item->adminApply->count(),
                    'channel'               =>  $item->channel,
                    'role'                  =>  $item->role ? $item->role->module : 'admin',
                    'role_name'             =>  $item->role ? $item->role->name : '超级管理员',
                    'avatar'                =>  $item->avatar,
                    'ip'                    =>  $item->ip,
                    'isAdmin'                =>  $item->isAdmin,
                    'status'                =>  $item->status,
                    'createStoreNum'        => $item->createStoreNum,
                    'login_time'            =>  $item->login_time,
                    'last_login_time'       =>  $item->last_login_time,
                    'statusFormat'       =>  $item->statusFormat,
                    'created_at'            =>  $item->created_at->format('Y-m-d H:i:s'),
                    'updated_at'            =>  $item->updated_at->format('Y-m-d H:i:s'),
                    'deleted_at'            =>  empty($item->deleted_at) ? '-' : $item->deleted_at->format('Y-m-d H:i:s'),
                ];
            }),
            'label' => [
                'userCount' => Admin::whereNotIn('id', [1])->where(['isAdmin' => 1])->whereIn('status', [1])->count(),
                'todayUserCount' => Admin::whereNotIn('id', [1])->whereIn("status", [0, 1, 2])->where(['isAdmin' => 1])->where('created_at', '>=', date("Y-m-d 00:00:00", time()))->where('created_at', '<=', date("Y-m-d 23:59:59", time()))->count(),
                'chenmoCount' => Admin::whereNotIn('id', [1])->where(['isAdmin' => 1])->where('login_time', '<=', date("Y-m-d 00:00:00", time() - 86400 * 30))->count(),
                'laheiCount' => Admin::whereNotIn('id', [1])->where(['isAdmin' => 1])->where('status', 2)->count(),
                'auditCount' => Admin::whereNotIn('id', [1])->where(['isAdmin' => 1])->where('status', 0)->count(),
                'recycleCount' => Admin::onlyTrashed()->count()
            ],
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
