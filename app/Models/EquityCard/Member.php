<?php

namespace App\Models\EquityCard;

use App\Models\BaseModel;
use App\Models\Order\OrderGoods;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends BaseModel
{
    protected $table = 'equity_card_member';
    use HasFactory;
    protected $fillable = [
        'uniacid',
        'equityCardId',
        'orderSn',
        'storeId',
        'startTime',
        'endTime',
        'nextTime',
        'userId'
    ];
    protected $appends = [
        'orderCount'
    ];


    public function equityCard()
    {
        return $this->hasOne(Card::class, 'id', 'equityCardId')->withTrashed();
    }

    public function getOrderCountAttribute()
    {
        return collect($this->goods()->select(['orderSn'])->groupBy(with(new OrderGoods())->getTable() . '.orderSn')->get())->count();
    }

    public function goods()
    {
        return $this->hasMany(OrderGoods::class, 'equityCardMemberId', 'id')->whereIn('state', [2, 3, 4, 5, 6, 10]);
    }
}
