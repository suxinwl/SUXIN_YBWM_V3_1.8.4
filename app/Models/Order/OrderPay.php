<?php

namespace App\Models\Order;

use App\Enums\PayEnum;
use App\Models\BaseModel;
use App\Models\CostomPay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPay extends BaseModel
{
    use HasFactory;
    protected $table = 'order_pay';
    protected $fillable = [
        'uniacid', 'orderSn', 'prentOrderSn', "thirdNo",
        "payType",
        "payTempId",
        "profit_sharing",
        "payChannel",
        'money',
        'state',
        'refundMoney'
    ];
    protected $appends = [
        'payChannelFormat',
        'money',
        'payTypeFormat'
    ];

    /**
     * 支付方式
     */
    public function getPayChannelFormatAttribute()
    {
        $data = [0 => '-', 1 => '店铺收款', 2 => '门店收款'];
        return $data[$this->payChannel];
    }

    public function getMoneyAttribute()
    {
        if ($this->payType == 8) {
            return intval($this->attributes['money']);
        }
        return $this->attributes['money'];
    }

    /**
     * 支付方式
     */
    public function getPayTypeFormatAttribute()
    {
        if ($this->state == 1) {
            return "未支付";
        } elseif ($this->payType > 100) {
            $pay = CostomPay::find(intval(mb_substr($this->payType, -1, 2, 'utf-8')));
            return $pay->name;
        } else {
            return PayEnum::format($this->payType);
        }
    }
}
