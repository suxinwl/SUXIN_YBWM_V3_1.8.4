<?php

namespace App\Models;

use App\Models\Admin\Apply;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends BaseModel
{
    use HasFactory;
    protected $table = 'sms_log';
    protected $guarded = [];

    protected $appends = [
        'stateFormat', 'channelFormat', 'roleFormat'
    ];
    public function getStateFormatAttribute()
    {
        return $this->state == 0  ? "失败" : "成功";
    }

    public function getChannelFormatAttribute()
    {
        $data = [
            'pay' => '订单支付通知',
            'receive' => "门店接单通知",
            'takeMeal' => "取餐通知",
            'delivery' => "订单配送通知",
            'refundApply' => "退款申请状态通知",
            'integralChange' => '积分变动通知',
            'balanceChange' => '余额变动通知',
            'vipChange' => "会员等级变动通知",
            'newOrder' => "新订单通知",
            'refundOrder' => '申请退款通知',
            'marketingSms' => '短信营销'
        ];
        return isset($data[$this->channel]) ? $data[$this->channel] : '-';
    }

    public function getRoleFormatAttribute()
    {
        $data = [
            'pay' => '用户',
            'receive' => "用户",
            'takeMeal' => "用户",
            'delivery' => "用户",
            'refundApply' => "用户",
            'integralChange' => '用户',
            'balanceChange' => '用户',
            'vipChange' => "用户",
            'newOrder' => "门店",
            'refundOrder' => '门店'
        ];
        return isset($data[$this->channel]) ? $data[$this->channel] : '-';
    }

    protected $casts =  [
        'res' => 'array'
    ];
    public function apply()
    {
        return $this->hasOne(Apply::class, 'id', 'uniacid');
    }
}
