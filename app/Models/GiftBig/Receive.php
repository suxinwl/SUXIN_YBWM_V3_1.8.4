<?php

namespace App\Models\GiftBig;

use App\Models\BaseModel;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receive extends BaseModel
{
    use HasFactory;
    protected $table = 'member_gift_big_receive';
    protected $guarded = [];
    protected $casts =  [
        'coupon'=> 'array',
        'data' => 'array',
    ];
    public function  activities()
    {
        return $this->hasOne(GiftBig::class, 'id', 'bigId');
    }
    public function  member()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }
}
