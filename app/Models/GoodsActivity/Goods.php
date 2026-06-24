<?php

namespace App\Models\GoodsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Goods extends Model
{
    use HasFactory;
    protected $table = 'goods_activity_goods';
    protected $fillable = [
        'uniacid', 'userType', 'discountRule', 'rule', 'activityId', 'type', 'spuId', 'discountType', 'wmDiscount', 'dnDiscount', 'startTime', 'endTime', 'state', 'discountLabel'
    ];
    protected $appends = [
        'discountLabel'
    ];
    protected $casts =  [
        'rule' => 'array',
        'scenario' => 'array',
        'discountRule' => 'array',
    ];
    public function stores()
    {
        return $this->hasMany(Store::class, 'activityId', 'activityId');
    }

    public function getDiscountLabelAttribute()
    {
        if (!$this->attributes['discountLabel']) {
            $data = [
                6 => '会员专享',
                7 => '第二件半价',
                8 => "买一赠一",
                9 => '第N件优惠'
            ];
            return $data[$this->type];
        }
        return $this->attributes['discountLabel'];
    }

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            return Cache::tags("goodsDiscount:$model->spuId")->flush();
        });
        static::saved(function ($model) {
            return Cache::tags("goodsDiscount:$model->spuId")->flush();
        });
        static::updating(function ($model) {
            return Cache::tags("goodsDiscount:$model->spuId")->flush();
        });
    }
}
