<?php

namespace App\Models;

use App\Enums\PayEnum;
use App\Models\Store\StoreBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StatisticsOrder extends BaseModel
{
    protected $table = 'statistics_order';
    use HasFactory;
    protected  $with = [
        'store'
    ];
    protected  $fillable = [
        'costomPayId',
        'uniacid',
        'storeId',
        'orderSn',
        'type',
        'diningType',
        'scene',
        'score',
        'payType',
        'payChannel',
        'adminId',
        'state',
        'day',
        'h',
        'hFormat',
        'money',
        'sellMoney',
        'refundMoney',
        'orderCount',
        'refundOrder',
        'discountMoney',
        'deliveryMoney',
        'boxMoney',
        'tableMoney',
        'userId',
        'storedValueMoney',
        'thirdNo',
        'payTime'
    ];
    protected $appends = [
        'typeFormat',
        'payTypeFormat',
        'scoreFormat',
        'hFormat',
        'day',
        'payTypeChannel'
    ];


    public function getTypeFormatAttribute()
    {
        if ($this->type == 1) {
            if ($this->scene == 1) {
                return "外卖订单";
            } elseif ($this->scene == 2) {
                return  "自提订单";
            }
        } else {
            $data = [
                2 => "储值订单",
                3 => "买单订单",
                4 => "店内订单",
                5 => "积分兑换",
                6 => "优惠券包"
            ];
            return $data[$this->type];
        }
    }
    public function getPayTypeFormatAttribute()
    {
        if ($this->payType > 100) {
            $pay = CostomPay::withTrashed()->find(intval(substr($this->payType,3)));
            return $pay->name;
        } else {
            return PayEnum::format($this->payType);
        }
    }

    public function getScoreFormatAttribute()
    {
        return appTypeFormat($this->score);
    }

    public function costomPay()
    {
        return $this->hasOne(CostomPay::class, 'id', 'costomPayId')->withTrashed();
    }

    public function getDayAttribute()
    {
        return date("m-d", strtotime($this->attributes['day']));
    }

    public function getHFormatAttribute()
    {
        if ($this->h == 0) {
            return "0:00-1:00";
        } elseif ($this->h == 23) {
            return "23:00-0:00";
        } else {
            return "{$this->h}:00-" . ($this->h + 1) . ":00";
        }
    }
    public function store()
    {
        return $this->hasOne(StoreBase::class,'id','storeId')->select(['id','name','isolate']);
    }


    public function getPayTypeChannelAttribute()
    {
        if ($this->payType >= 11 && $this->payType <= 19) {
            return PayEnum::WECHAT_JSAPI;
        } elseif ($this->payType >= 20 && $this->payType <= 29) {
            return PayEnum::ALIPAY_PAY;
        } else {
            return $this->payType;
        }
    }
}
