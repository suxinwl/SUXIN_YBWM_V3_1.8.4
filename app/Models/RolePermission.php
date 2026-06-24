<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends BaseModel
{
    public function permission(){
        return $this->hasOne('App\Models\Permission','id','permission_id')->withTrashed();
    }
}
