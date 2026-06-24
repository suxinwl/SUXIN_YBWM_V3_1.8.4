<?php

namespace App\Models\ExchangeCode;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeCode extends BaseModel
{
    use HasFactory;
    protected $table = 'exchange_code';
    protected $fillable = ['sn', 'startTime', 'endTime', 'uniacid', 'name', 'type', 'num', 'giveType', 'couponGive', 'balance', 'limitDaySwitct', 'limitDay', 'limitSwitct', 'limit', 'body'];
    protected $casts =  [
        'couponGive' => 'array',
    ];
    protected $appends = [
        'stateFormat', 'subStateFormat'
    ];
    public function receives()
    {
        return $this->hasMany(ExchangeCodeReceive::class, 'exchangeCodeId', 'id');
    }

    public function code()
    {
        return $this->hasOne(ExchangeCodeReceive::class, 'exchangeCodeId', 'id')->where('display', 0)->where('type', 2);
    }


    public function getSubStateFormatAttribute()
    {
        if (time() < strtotime($this->startTime)) {
            return "未开始";
        } elseif (time() >= strtotime($this->startTime) && time() <= strtotime($this->endTime)) {
            return "进行中";
        } else {
            return "已结束";
        }
    }

    public function getStateFormatAttribute()
    {
        $data = [1 => '正常', 2 => '作废'];
        return $data[$this->state];
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->sn = empty($model->sn) ? CouponRandInt(10) : $model->sn;
        });
    }
}
