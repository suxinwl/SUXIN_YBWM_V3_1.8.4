<?php

namespace App\Models\EquityCard;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goods extends BaseModel
{
    protected $table = 'equity_card_goods';
    use HasFactory;
    protected $fillable = [
        'uniacid',
        'equityCardId',
        'spuId',
        'discountType',
        'wmDiscount',
        'dnDiscount',
        'rule',
        'discountRule',
    ];
    protected $casts =  [
        'rule' => 'array',
        'discountRule' => 'array',
    ];
}
