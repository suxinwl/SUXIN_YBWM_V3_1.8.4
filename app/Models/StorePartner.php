<?php

namespace App\Models;

use App\Models\Member\MemberBase;
use App\Models\Member;
use App\Models\Store\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
class StorePartner extends BaseModel
{
    protected $table = 'store_partner';
    protected $fillable = ['uniacid', 'profix', 'parentId', 'state', 'userId', 'storeId'];
    use HasFactory;
    protected $casts =  [
        'profix' => 'array'
    ];
    protected $with = [
        'user'
    ];
    protected $appends = [
        'stateFormat','store','user'
    ];
    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId')->select(['id', 'nickname', 'mobile']);
    }
    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
    public function account()
    {
        return $this->hasOne(Account::class, 'storeId', 'storeId');
    }
    public function sub()
    {
        return $this->hasMany(Member::class, 'storeId', 'storeId');
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

    public function getStoreAttribute()
    {
        return DB::table('store')->select(['id', 'name'])->where('id', $this->storeId)->first();
    }
    public function getUserAttribute()
    {
        return DB::table('member')->where('storeId', $this->storeId)->first();
    }

    public function order()
    {
        return $this->hasMany(StorePartnerOrder::class, 'partnerId', 'userId');
    }



}
