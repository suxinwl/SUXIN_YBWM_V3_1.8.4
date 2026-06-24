<?php

namespace App\Models\NewSub;

use App\Models\GoodsSpu;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewSub extends Model
{
    use HasFactory;
    protected $table = 'newsub';
    protected $fillable = ['uniacid','scenario','type', 'name', 'state', 'sort', 'money', 'goodsType', 'goodsIds', 'storeType', 'storeIds', 'startTime', 'endTime'];
    protected $casts =  [
        'scenario' => 'array',
        'goodsIds' => 'array',
        'storeIds' => 'array',
    ];
    protected $appends = [
        'stateFormat'
    ];
    protected $attributes = [
        'sort' => 0
    ];
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'newsub_stores', 'newSubId', 'storeId');
    }


    public function goods()
    {
        return $this->belongsToMany(GoodsSpu::class, 'newsub_goods', 'newSubId', 'spuId');
    }

    public function getStateFormatAttribute()
    {
        if (time() < strtotime($this->startTime)) {
            return "未开始";
        } elseif (time() >= strtotime($this->startTime) && time() <= strtotime($this->endTime)) {
            return "进行中";
        } else {
            return "已结束";
        }
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->sn = CouponRandInt(10);
        });
    }
}
