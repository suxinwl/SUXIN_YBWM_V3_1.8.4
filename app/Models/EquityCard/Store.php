<?php

namespace App\Models\EquityCard;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends BaseModel
{
    protected $table = 'equity_card_store';
    use HasFactory;
    protected $fillable = [
        'uniacid',
        'orderSn',
        'storeId',
        'userId',
        'startTime',
        'endTime',
        'state',
        'equityCardId',
        'score',
        'money',
        'sellMoney',
        'refundMoney',
        'completionTime',
        'payTime'
    ];
}
