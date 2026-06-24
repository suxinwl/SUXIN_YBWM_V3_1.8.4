<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;
use App\Models\Store as ModelsStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends BaseModel
{
    use HasFactory;
    protected $table = 'delivery_store';
    protected $fillable = ['ruleId','makeMinutes', 'storeId', 'estimate', 'startRule', 'uniacid', 'name', 'desc', 'channel', 'deliveryType', 'deliveryData', 'receivingMinutes', 'advanceOrderMinutes', 'advanceOrderType', 'loseType', 'loseNum', 'kmMinutes', 'kmPushMinutes', 'km', 'priceType', 'priceFixData', 'priceDistanceData', 'priceAreaData'];
    protected $casts =  [
        'channel' => 'array',
        'deliveryData' => 'array',
        'priceFixData' => 'array',
        'priceDistanceData' => 'array',
        'priceAreaData' => 'array',
        'startRule' => 'array'
    ];
    public function store()
    {
        return $this->hasOne(ModelsStore::class, 'id', 'storeId');
    }

    public function rule()
    {
        return $this->hasOne(Rule::class, 'id', 'ruleId');
    }
}
