<?php

namespace App\Models\Member;

use App\Models\BaseModel;
use App\Models\Coupon\Coupon;
use App\Models\Member;
use App\Models\Member\Vip as MemberVip;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vip extends BaseModel
{
    protected $table = 'member_vip';
    use HasFactory, SoftDeletes;

    protected $fillable = ['sort', 'storeId','extPower', 'couponSwitch', 'couponGive', 'uniacid', 'name', 'styleSwitch', 'style', 'exp', 'balanceSwitch', 'balance', 'integralSwitch', 'integral', 'discountSwitch', 'discount', 'integralMultiplierSwitch', 'integralMultiplier', 'freeMailSwitch', 'freeMailLimit','weekArr'];

    protected $appends = [
        'state'
    ];
    protected $casts =  [
        'couponGive' => 'array',
        'extPower' => 'array',
        'weekArr' => 'array'
    ];

    public function getLevel()
    {
        $level = self::where('uniacid', $this->uniacid)->count();
        return intval($level) + 1;
    }
    public function getStateAttribute()
    {
        return intval(empty($this->deleted_at));
    }

    public function getNextVipAttribute()
    {
        return Vip::where('uniacid', $this->uniacid)->where('exp', ">", $this->exp)->orderBy('exp', 'asc')->first();
    }

    public function member()
    {
        return $this->hasMany(Member::class, "vipId", 'id');
    }

    public  function getCouponListAttribute()
    {
        if (!$this->_couponList) {
            $ids = collect($this->couponGive)->pluck('id')->all();
            if ($ids) {
                $this->_couponList = Coupon::whereIN('id', $ids)->get();
            }
        }
        return $this->_couponList;
    }

    public function getPowerAttribute()
    {
        $power = [];
        if ($this->balanceSwitch) {
            $power[] = 'balance';
        }
        if ($this->integralSwitch) {
            $power[] = 'integral';
        }
        if ($this->discountSwitch) {
            $power[] = 'discount';
        }
        if ($this->integralMultiplierSwitch) {
            $power[] = 'integralMultiplier';
        }
        if ($this->freeMailSwitch) {
            $power[] = 'freeMail';
        }
        if ($this->couponSwitch) {
            $power[] = 'couponGive';
        }
        $list = VipPower::where('uniacid', $this->uniacid)
            ->where(function ($q) use ($power) {
                return $q->whereIn('type', $power ?? [0]);
            })->get();
        return collect($list)->keyBy('type')->all();
    }

    public function getExtPowerDataAttribute()
    {
        if ($this->extPower) {
            return VipPower::where('uniacid', $this->uniacid)
                ->whereIn('id', $this->extPower)
                ->get();
        }
        return [];
    }
}
