<?php

namespace App\Models;

use App\Models\Coupon\MemberCoupon;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class MemberAccount extends BaseModel
{
    use HasFactory;
    protected $table = 'member_account';
    protected $guarded = [];
    protected $attributes = [
        'balance' => 0,
        "integral" => 0,
        'luckyAttempts' => 0,
        'canWithdrawalAmount' => 0,
        'withdrawalAmount' => 0,
        'withdrawalCompleteAmount' => 0
    ];

    protected $appends = [
        'balance', 'storeBuy', 'earnings'
    ];

    protected $casts =  [
        'withdrawalConfig' => 'array',
    ];

    public function member()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }

    public function getBalanceAttribute($value)
    {
        $storeId  = Request()->header('storeId') ??  Request()->storeId ?? 0;
        if (empty($storeId)) {
            return $this->attributes['balance'];
        } else {
            $storConfig = ConfigService::getChannelConfig('storageVal', $this->uniacid);
            if ($storConfig) {
                if ($storConfig['storeType'] == 1) {
                    return $this->attributes['balance'];
                }
                if ($storConfig['storeType'] == 2 && in_array($storeId, $storConfig['storeId'])) {
                    return $this->attributes['balance'];
                }
                if ($storConfig['storeType'] == 3 && in_array($storeId, $storConfig['storeId'])) {
                    return '0.00';
                }
            }
            return '0.00';
        }
    }

    public function getStoreBuyAttribute($value)
    {
        $storeId  = Request()->header('storeId') ??  Request()->storeId ?? 0;
        if (empty($storeId)) {
            return true;
        } else {
            $storConfig = ConfigService::getChannelConfig('storageVal', $this->uniacid);
            if ($storConfig) {
                if ($storConfig['storeType'] == 1) {
                    return true;
                }
                if ($storConfig['storeType'] == 2 && in_array($storeId, $storConfig['storeId'])) {
                    return true;
                }
                if ($storConfig['storeType'] == 3 && in_array($storeId, $storConfig['storeId'])) {
                    return false;
                }
            }
            return false;
        }
    }


    public function getBalance($storeId)
    {
        if (empty($storeId)) {
            return $this->attributes['balance'];
        } else {
            $storConfig = ConfigService::getChannelConfig('storageVal', $this->uniacid);
            if ($storConfig) {
                if ($storConfig['storeType'] == 1) {
                    return $this->attributes['balance'];
                }
                if ($storConfig['storeType'] == 2 && in_array($storeId, $storConfig['storeId'])) {
                    return $this->attributes['balance'];
                }
                if ($storConfig['storeType'] == 3 && in_array($storeId, $storConfig['storeId'])) {
                    return '0.00';
                }
            }
            return '0.00';
        }
    }

    public function getCommissionAttribute()
    {
        return bcadd($this->canWithdrawalAmount, $this->freezeAmount, 2);
    }

    public function getEarningsAttribute()
    {
        return bcadd(bcadd(bcadd($this->canWithdrawalAmount, $this->withdrawalCompleteAmount, 2), $this->withdrawalAmount, 2), $this->freezeAmount, 2);
    }

    public function log()
    {
        return $this->hasMany(MemberAccountLog::class, 'userId', 'id');
    }
    public function coupon()
    {
        return $this->hasMany(MemberCoupon::class, 'userId', 'userId');
    }

    public static  function scopeMoney($query)
    {
        return $query->select(DB::raw("IFNULL(sum(if(id > 0 ,1,0)),0) as userCount,IFNULL(sum(if(id > 0 ,canWithdrawalAmount,0)),0) as canWithdrawalAmount,IFNULL(sum(if(id > 0,withdrawalAmount,0)),0) as withdrawalAmount,IFNULL(sum(if(id > 0 ,withdrawalCompleteAmount,0)),0) as withdrawalCompleteAmount"));
    }

    public function getStateFormatAttribute()
    {
        return $this->stateFormat();
    }

    public function scopeReview($query)
    {
        $query->where('state', 0);
    }

    public function scopePass($query)
    {
        $query->where('state', 1);
    }
    public function scopeReject($query)
    {
        $query->where('state', 2);
    }
    public function scopeCancel($query)
    {
        $query->where('state', 3);
    }

    public function stateFormat()
    {
        $data = [
            0 => "提现中",
            1 => "提现成功",
            2 => "已驳回",
            3 => "已取消"
        ];
        return $data[$this->state];
    }


}
