<?php

namespace App\Models;

use Alipay\EasySDK\Kernel\Base;
use App\Models\Admin\Apply;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsOrder extends BaseModel
{
    protected $table = 'sms_pay_order';
    use HasFactory;
    protected $guarded = [];

    public function account()
    {
        return $this->hasOne(SmsAccount::class, 'uniacid', 'uniacid');
    }

    public function apply()
    {
        return $this->hasOne(Apply::class, 'id', 'uniacid');
    }

    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'userId');
    }
}
