<?php

namespace App\Models\FullSub;

use App\Models\GoodsSpu;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FullSub extends Model
{
    use HasFactory;
    protected $table = 'fullsub';
    protected $fillable = ['uniacid', 'type', 'name', 'rulesType', 'storeId', 'state', 'sort', 'scenario', 'userType', 'rules', 'threshold', 'goodsType', 'goodsIds', 'storeType', 'storeIds', 'startTime', 'endTime'];
    protected $casts =  [
        'scenario' => 'array',
        'rules' => 'array',
        'threshold' => 'array',
        'goodsIds' => 'array',
        'storeIds' => 'array',
    ];
    protected $appends = [
        'stateFormat'
    ];
    protected $attributes = [
        'sort' => 0
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'fullsub_stores', 'fullsubId', 'storeId');
    }


    public function goods()
    {
        return $this->belongsToMany(GoodsSpu::class, 'fullsub_goods', 'fullsubId', 'spuId');
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
