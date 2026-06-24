<?php

namespace App\Models\GoodsActivity;

use App\Models\GoodsSearch\GoodsSpuBase;
use App\Models\GoodsSpu;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;
    protected $table = 'goods_activity';
    protected $fillable = [
        'uniacid','storeId','userIds', 'userType', 'mutex','discountRule', 'type', 'rule', 'title', 'startTime', 'endTime', 'name', 'sn', 'state', 'sort', 'scenario', 'goodsData', 'storeType', 'storeIds', 'discountLabel'
    ];
    protected $casts =  [
        'scenario' => 'array',
        'goodsData' => 'array',
        'storeIds' => 'array',
        'rule' => 'array',
        'discountRule' => 'array',
        'userIds' => 'array'
    ];

    public function goods()
    {
        return $this->hasMany(Goods::class, 'activityId', 'id');
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'goods_activity_stores', 'activityId', 'storeId');
    }
}
