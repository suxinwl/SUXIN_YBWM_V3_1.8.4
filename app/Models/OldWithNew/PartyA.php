<?php

namespace App\Models\OldWithNew;

use App\Models\BaseModel;
use App\Models\Member\MemberBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartyA extends BaseModel
{
    use HasFactory;
    protected $table = 'old_with_new_party_a';
    protected $fillable = ['uniacid','storeId', 'userId', 'oldWithNewId', 'partyBCount', 'exchangeCount', 'integral', 'couponCount'];

    public function partyB()
    {
        return $this->hasMany(PartyB::class, 'partyAid', 'userId');
    }

    public function activity()
    {
        return $this->hasOne(Activity::class, 'id', 'oldWithNewId');
    }

    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId')->select(['id', 'mobile', 'nickname']);
    }
}
