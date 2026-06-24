<?php

namespace App\Http\Resources\Admin\AdminResource;

use App\Models\Admin\Apply;
use App\Services\RoleService;
use Illuminate\Http\Resources\Json\JsonResource;

class Admin extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $apply_cout =  $this->id == 1 ?  Apply::withTrashed()->count()  : $this->adminApply->count();
        $role =  $this->role ? $this->role->module : 'admin';
        if ($request->header('appType') == "store") {
            if ($role != "store") {
                $roleData = collect(arreach(RoleService::storeRole()))->pluck('role');
            } else {
                $roleData = $this->role->storRole ?? [];
            }
        }
        if ($request->header('appType') == "cashier") {
            if ($role != "store") {
                $roleData = collect(arreach(RoleService::cashierRole()))->pluck('role');
            } else {
                $roleData = $this->role->cashierRole ?? [];
            }
        }
        return [
            'id'                    =>  $this->id,
            'username'              =>  $this->username,
            'nickname'              =>  $this->nickname,
            'role_id'               =>  $this->role_id,
            'mobile'                =>  $this->mobile,
            'data'                  =>  $this->data,
            'uniacid'                  =>  $this->uniacid,
            'group_id'              =>  $this->group_id,
            'group_name'            =>  $this->group->title,
            'apply_cout'                    => $apply_cout,
            'channel'               => $this->channel,
            'role'                  => $role,
            "roleData" => $roleData,
            'role_name'             =>  $this->role ? $this->role->name : '超级管理员',
            // 'stores'                => $this->stores,
            'avatar'                =>  $this->avatar,
            'ip'                    =>  $this->ip,
            'status'                    =>  $this->status,
            'storeId' => $this->storeId,
            'apply' => $this->apply,
            'createStoreNum' => $this->createStoreNum,
            'login_time'            =>  $this->login_time,
            'last_login_time'       =>  $this->last_login_time,
            'uniacid' => $this->uniacid,
            'isAdmin' => $this->isAdmin,
            'wxBind' => $this->wxBind(),
            'goUrl' => '',
            'created_at'            =>  $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'            =>  $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
