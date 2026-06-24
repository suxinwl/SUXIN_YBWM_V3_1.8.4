<?php

namespace App\Models\OldWithNew;

use App\Models\BaseModel;
use App\Models\Member\MemberBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartyB extends BaseModel
{
    use HasFactory;
    protected $table = 'old_with_new_party_b';
    protected $fillable = ['uniacid', 'storeId', 'userId', 'oldWithNewId', 'partyAid', 'partyBstate', 'firstPayState', 'partyBCount', 'exchangeCount', 'integral', 'couponCount', 'partyAData'];
    protected $casts =  [
        'partyAData' => 'array',
        'data' => 'array'
    ];

    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId')->select(['id', 'nickname', 'mobile']);
    }
    public function partyAUser()
    {
        return $this->hasOne(MemberBase::class, 'id', 'partyAid')->select(['id', 'nickname', 'mobile']);
    }
}
