<?php

namespace App\Models;

use App\Models\Member\MemberBase;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends BaseModel
{
    protected $table = 'partner';
    protected $fillable = ['uniacid', 'profix', 'parentId', 'state', 'userId', 'storeId'];
    use HasFactory;
    protected $casts =  [
        'profix' => 'array'
    ];
    protected $with = [
        'user', 'parent'
    ];
    protected $appends = [
        'stateFormat'
    ];
    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId')->select(['id', 'nickname', 'mobile']);
    }
    public function sub()
    {
        return $this->hasMany(Member::class, 'partnerId', 'userId');
    }
    public function getStateFormatAttribute()
    {
        $data = [
            0 => '待审核',
            1 => "正常",
            2 => '已驳回'
        ];
        return $data[$this->state];
    }


    public function parent()
    {
        return $this->hasOne(MemberBase::class, 'id', 'parentId')->select(['id', 'nickname', 'mobile']);
    }

    public function order()
    {
        return $this->hasMany(PartnerOrder::class, 'partnerId', 'userId');
    }

    public function downline()
    {
        return $this->hasMany(MemberBase::class, 'partnerId', 'userId');
    }
    public function account()
    {
        return $this->hasOne(MemberAccount::class, 'userId', 'userId');
    }
}
