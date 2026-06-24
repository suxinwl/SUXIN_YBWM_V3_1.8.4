<?php

namespace App\Models\EquityCard;

use App\Models\BaseModel;
use App\Models\Member\MemberBase;
use App\Models\Order\OrderIndex;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Order extends BaseModel
{
    protected $table = 'equity_card_order';
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


    protected $appends = [
        'stateFormat'
    ];

    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            try {
                OrderIndex::create([
                    'orderSn' => $model->orderSn,
                    'type' => 8,
                    'payType' => 0,
                    'userId' => $model->userId,
                    'score' => $model->score,
                    'uniacid' => $model->uniacid,
                    'storeId' => $model->storeId,
                ]);
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });
    }

    public function equityCard()
    {
        return $this->hasOne(Card::class, 'id', 'equityCardId')->withTrashed();
    }

    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId');
    }

    public function getStateFormatAttribute()
    {
        $data = [
            0 => "已取消",
            1 => "待支付",
            6 => "已完成",
            8 => "已退款"
        ];
        return $data[$this->state];
    }
}
