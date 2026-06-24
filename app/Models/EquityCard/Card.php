<?php

namespace App\Models\EquityCard;

use App\Models\BaseModel;
use App\Models\Coupon\Coupon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;

class Card extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    protected $cachePrefix = "equityCard";
    protected $table = 'equity_card';
    protected $fillable = [
        'sort',
        'uniacid',
        'storeId',
        'userId',
        'state',
        'name',
        'desc',
        'imageType',
        'image',
        'textColor',
        'themeColor',
        'money',
        'day',
        'dayType',
        'dayStart',
        'dayEnd',
        'startTime',
        'endTime',
        'buyType',
        'discountSwitch',
        'discountTitle',
        'discountDesc',
        'discountShow',
        'goodsType',
        'goods',
        'discountType',
        'discountRule',
        'couponSwitch',
        'couponGive',
        'periodCouponGive',
        'deliveryFreeSwitch',
        'deliveryFreeTitle',
        'deliveryFreeDesc',
        'deliveryFreeMoney',
        'storeType',
        'storeIds'
    ];

    protected $hidden = [
        'order'
    ];

    protected $casts =  [
        'goods' => 'array',
        'discountRule' => 'array',
        'couponGive' => 'array',
        'periodCouponGive' => 'array',
        'deliveryFreeGoods' => 'array',
        'storeIds' => 'array'
    ];



    public function order()
    {
        return $this->hasOne(Order::class, 'equityCardId', 'id')->orderBy('id', 'desc');
    }

    public function getIsBuyAttribute()
    {
        $userCard = Member::where('userId', auth('user')->user()->id)->where('endTime', ">=", Carbon::now()->toDateTimeString())->first();
        if ($userCard) {
            return 0;
        }
        $order = Member::where('userId', auth('user')->user()->id)
            ->where('endTime', ">=", Carbon::now()->toDayDateTimeString())
            ->where('equityCardId', $this->id)
            ->first();
        if ($this->buyType == 1) {
            return $order ? 0 : 1;
        } else {
            if ($order  && $order->endTime > Carbon::now()->toDateTimeString()) {
                return 0;
            }
            return 1;
        }
    }

    public function getCouponGiveAttribute()
    {
        if ($this->attributes['couponGive']) {
            $list = json_decode($this->attributes['couponGive'], true);
            return collect($list)->map(function ($coupon) {
                $coupon['coupon'] = Coupon::find($coupon['id']);
                return $coupon;
            });
        }
        return [];
    }

    public function getPeriodCouponGiveAttribute()
    {
        if ($this->attributes['periodCouponGive']) {
            $list = json_decode($this->attributes['periodCouponGive'], true);
            $list['couponGive'] =  collect($list['couponGive'])->map(function ($coupon) {
                $coupon['coupon'] = Coupon::find($coupon['id']);
                return $coupon;
            });
            return $list;
        }
        return [];
    }
}
