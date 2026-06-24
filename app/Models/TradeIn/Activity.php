<?php

namespace App\Models\TradeIn;

use App\Models\BaseModel;
use App\Models\GoodsSearch\GoodsSpuBase;
use App\Models\GoodsSpu;
use App\Models\Store;
use App\Models\Store\StoreBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends BaseModel
{
    use HasFactory;
    protected $table = 'tradein';
    protected $fillable = [
        'storeId', 'uniacid', 'userIds', 'userType', 'discountRule', 'type', 'rule', 'title', 'startTime', 'endTime', 'name', 'sn', 'state', 'sort', 'scenario', 'goodsData', 'storeType', 'storeIds', 'discountLabel'
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
        return $this->belongsToMany(StoreBase::class, 'tradein_stores', 'activityId', 'storeId');
    }
}
