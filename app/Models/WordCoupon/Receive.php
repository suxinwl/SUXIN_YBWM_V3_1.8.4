<?php

namespace App\Models\WordCoupon;

use App\Models\BaseModel;
use App\Models\Member\MemberBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receive extends BaseModel
{
    public $_couponList;
    use HasFactory;
    protected $table = 'word_coupon_receive';
    protected $fillable = [
        'uniacid',
        'wordCouponId',
        'userId',
        'balance',
        'integral',
        'coupon',
        'data',
        'storeId'
    ];
    protected $casts =  [
        'coupon' => 'array'
    ];

    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId')->select([
            'id', 'mobile', 'nickname'
        ]);
    }

    public function wordCoupon()
    {
        return $this->hasOne(Coupon::class, 'id', 'wordCouponId')->select(['id', 'name']);
    }
}
