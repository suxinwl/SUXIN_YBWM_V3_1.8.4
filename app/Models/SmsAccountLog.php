<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsAccountLog extends BaseModel
{
    use HasFactory;
    protected $table = 'sms_account_log';
    protected $guarded = [];
    
    protected $appends = [
        'format'
    ];
    const  BASE = 0;
    const  TOPUP = 1;
    const  GIVING = 2;

    public function getFormatAttribute()
    {
        return $this->format();
    }

    public function order()
    {
        return $this->hasOne(SmsOrder::class, 'outTradeNo', 'orderSn');
    }
    public function format()
    {
        $data = [
            self::BASE => "系统调整",
            self::GIVING => "系统赠送",
            self::TOPUP => "充值"
        ];
        return $data[$this->behavior];
    }
}
