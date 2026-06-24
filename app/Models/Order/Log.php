<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends BaseModel
{
    use HasFactory;
    protected $table = 'order_log';
    protected $fillable = ['uniacid', 'orderSn', 'state', 'log'];

    protected $appends = [
        'stateFormat'
    ];
    public function order()
    {
        return $this->hasOne(TakeOutOrder::class, 'orderSn', 'orderSn');
    }

    public function getStateFormatAttribute()
    {
        $data = [
            0 => "已取消",
            1 => "待支付",
            2 => "待接单",
            3 => "制作中",
            4 =>  $this->getState(),
            5 => "配送中",
            6 => "已完成",
            7 => "待审核",
            8 => "已退款",
            9=>"已拒绝"
        ];
        return $data[$this->state];
    }

    public function getState()
    {
        if ($this->order->diningType == 0) {
            return  "待配送";
        }
        if ($this->order->diningType == 1) {
            return  "待取单";
        }
        if ($this->order->diningType == 2) {
            return  "待取单";
        }
        return '';
    }
}
