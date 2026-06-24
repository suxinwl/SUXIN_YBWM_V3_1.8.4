<?php

namespace App\Models;

use App\Services\RoleService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends BaseModel
{
    protected $table = 'roles';
    use HasFactory, SoftDeletes;
    protected $fillable = ['name','storeId','uniacid', 'module', 'menu_id', 'permission_id', 'desc', 'appList', 'storRole'];
    protected $withCount = ['admins'];

    protected $casts =  [
        'appList' => 'array',
        'storRole' => 'array',
        'cashierRole' => 'array',
    ];
    public function permissions()
    {
        return $this->belongsToMany('App\Models\Permission', 'role_permissions', 'role_id', 'permission_id');
    }

    public function menus()
    {
        return $this->belongsToMany('App\Models\Menu', 'role_menus', 'role_id', 'menu_id');
    }

    public function admins()
    {
        return $this->hasMany(Admin::class, 'role_id', 'id');
    }
}
