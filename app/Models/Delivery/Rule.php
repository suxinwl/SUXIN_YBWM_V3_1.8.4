<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rule extends BaseModel
{
    use HasFactory;
    protected $table = 'delivery_rule';
    protected $fillable = ['makeMinutes','callType', 'estimate', 'startRule', 'uniacid', 'name', 'desc', 'channel', 'deliveryType', 'deliveryData', 'receivingMinutes', 'advanceOrderMinutes', 'advanceOrderType', 'loseType', 'loseNum', 'kmMinutes', 'kmPushMinutes', 'km', 'priceType', 'priceFixData', 'priceDistanceData', 'priceAreaData'];
    protected $casts =  [
        'channel' => 'array',
        'deliveryData' => 'array',
        'priceFixData' => 'array',
        'priceDistanceData' => 'array',
        'priceAreaData' => 'array',
        'startRule' => 'array'
    ];

}
