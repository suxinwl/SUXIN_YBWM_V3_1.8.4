<?php

namespace App\Models;

use App\Models\Admin\AdminBind;
use App\Models\Admin\Apply;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class Admin extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use SoftDeletes;
    protected $table = 'admins';
    protected $guarded = [];
    // protected $guard = 'admin';
    protected $casts =  [
        'data' => 'array',
        'storeId' => "array"
    ];
    protected $append = [
        'statusFormat'
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    protected $attributes = [
        'username' => '',
        'mobile' => '',
        'nickname' => '',
        'avatar' => '',
        'ip' => '',
        'type' => 0,
        'password' => '',
        'uniacid' => 0,
        'login_time' => NULL,
        'last_login_time' => NULL,
        'createStoreNum' => 0,
        'status' => 1,
        'data' => '',
        'isAdmin' => 0,
        'group_id' => 0,
        'channel' => 1,
        'operatorId' => 0,
        'subMessage' => 0
    ];

    public static function statusList()
    {
        return [
            0 => '待审核',
            1 => '正常',
            2 => '禁用',
            3 => '已拒绝'
        ];
    }

    public function getStatusFormatAttribute()
    {
        $data = self::statusList();
        return $data[$this->status];
    }

    public function roles()
    {
        return $this->belongsToMany('App\Models\Role', 'admin_role', 'admin_id', 'role_id');
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'admin_storeids', 'admin_id', 'store_id');
    }

    public function operator()
    {
        return $this->hasOne(Admin::class, 'id', 'operatorId')->select(['id', 'username', 'mobile']);
    }

    public function apply()
    {
        return $this->hasOne('App\Models\Admin\Apply', 'id', 'uniacid');
    }

    public function wxbind()
    {
        return AdminBind::where('userId', $this->id)->where('channel', 'open')->where('type', 'wechat')->first();
    }

    public function adminApply()
    {
        return $this->hasMany('App\Models\Admin\Apply', 'createUserId', 'id')->withTrashed();
    }

    public function group()
    {
        return $this->hasOne('App\Models\AdminGroup', 'id', 'group_id');
    }
    /**
     * 关联的角色
     */
    public function  role()
    {
        return $this->hasOne('App\Models\Role', 'id', 'role_id');
    }


    public function permissions()
    {
        return $this->hasMany('App\Models\Apply', 'id', 'uniacid');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['systemRole' => 'admin'];
    }

    public function selectApply()
    {
        $uniacid = request()->header('uniacid', 0);
        return  Apply::find($uniacid);
    }
}
