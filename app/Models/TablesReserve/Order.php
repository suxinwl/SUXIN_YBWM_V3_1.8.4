<?php

namespace App\Models\TablesReserve;

use App\Models\BaseModel;
use App\Models\Order\OrderIndex;
use App\Models\Store;
use App\Models\Tables\Area;
use App\Models\Tables\Type;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Order extends BaseModel
{
    use HasFactory;
    protected $table = 'table_reserve_order';
    protected $fillable = [
        'uniacid', 'userId', 'storeId', 'typeId', 'autoReceive', 'areaId', 'state', 'orderSn', 'notes', 'money', 'mobile', 'num', 'sellMoney', 'score', 'appointmentTime', 'reserveTime', 'expiredTime', 'contact', 'person'
    ];
    protected $with = [
        'orderIndex'
    ];
    protected $appends = [
        'stateFormat'
    ];

    public function getConfigAttribute()
    {
        return ConfigService::getStoreConfig('bookTable', $this->storeId);
    }

    public function type()
    {
        return $this->hasOne(Type::class, 'id', 'typeId');
    }

    public function area()
    {
        return $this->hasOne(Area::class, 'id', 'areaId');
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
    public function orderIndex()
    {
        return $this->hasOne(OrderIndex::class, 'orderSn', 'orderSn');
    }


    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->changeBeforState = $model->getOriginal('state') ?? 1;
            $model->expiredTime = null;
        });
        static::saved(function ($model) {
            try {
                if (!$model->orderIndex) {
                    OrderIndex::create([
                        'orderSn' => $model->orderSn,
                        'type' => 7,
                        'payType' => 0,
                        'userId' => $model->userId,
                        'score' => $model->score,
                        'uniacid' => $model->uniacid,
                        'storeId' => $model->storeId,
                        'orderId' => $model->id
                    ]);
                }
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });
    }

    public function getStateFormatAttribute()
    {
        $data = [
            0 => '已取消',
            1 => "待支付",
            2 => "待接单",
            3 => "已接单",
            6 => "已完成",
            8 => "已退款",
        ];
        return $data[$this->state];
    }

    /**
     * 已关闭
     */
    public function scopeClose($q)
    {
        return $q->where('state', 0);
    }


    /**
     * 待支付
     */
    public function scopeUnpaid($q)
    {
        return $q->where('state', 1);
    }

    /**
     * 已支付待接单
     */
    public function scopeUnReceived($q)
    {
        return $q->where('state', 2);
    }

    /**
     * 已接单制作中
     */
    public function scopeMaking($q)
    {
        return $q->where('state', 3);
    }



    /**
     * 已完成
     */
    public function scopeComplete($q)
    {
        return $q->where('state', 6);
    }


    /**
     * 已退款
     */
    public function scopeRefund($q)
    {
        return $q->where('state', 8);
    }
}
