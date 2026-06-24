<?php

namespace App\Http\Resources\Admin\Roles;

use Illuminate\Http\Resources\Json\JsonResource;

class Show extends JsonResource
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
            'uniacid'               =>  $this->uniacid,
            'name'                  =>  $this->name,
            'module'                =>  $this->module,
            'menus'                 =>  $this->menus->map(function ($item) {
                return $item->id;
            }),
            'permissions'           =>  $this->permissions->map(function ($permissions) {
                return $permissions->id;
            }),
        ];
    }
}
