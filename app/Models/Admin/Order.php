<?php

namespace App\Models\Admin;

use App\Enums\PayEnum;
use App\Http\Requests\Admin\Admin;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends BaseModel
{
    protected $table = 'admin_pay_order';
    use HasFactory;

    protected $appends = [
        'typeFormat', 'payTypeFormat'
    ];
    public function apply()
    {
        return $this->hasOne(Apply::class, 'id', 'applyId');
    }

    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'userId');
    }

    public function getTypeFormatAttribute()
    {
        $data = [
            1 => '套餐购买',
            2 => "套餐续费",
            3 => "短信购买"
        ];
        return $data[$this->type];
    }

    public function getPayTypeFormatAttribute()
    {
        return  ($this->payType == 0 || $this->state ==0) ? '-':PayEnum::format($this->payType) ;
    }
}
