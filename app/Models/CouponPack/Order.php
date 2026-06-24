<?php

namespace App\Models\CouponPack;

use App\Models\BaseModel;
use App\Models\Order\OrderIndex;
use App\Models\Store;
use App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Order extends BaseModel
{
    use HasFactory;
    protected $table = 'coupon_pack_order';
    protected $fillable = [
        'orderSn', 'couponGive', 'uniacid', 'storeId', 'userId', 'couponPackId', 'score', 'money', 'sellMoney', 'refundMoney', 'completionTime', 'state', 'payTime', 'couponGive'
    ];
    protected $casts =  [
        'couponGive' => "array"
    ];
    protected $with = ['orderIndex', 'store','activity'];
    protected $appends =[
        'stateFormat','orderTypeFormat','refundFormat'
    ];
    public function orderIndex()
    {
        return $this->hasOne(OrderIndex::class, 'orderSn', 'orderSn');
    }
    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }
    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            try {
                OrderIndex::create([
                    'orderSn' => $model->orderSn,
                    'type' => 6,
                    'payType' => 0,
                    'userId' => $model->userId,
                    'score' => $model->score,
                    'uniacid' => $model->uniacid,
                    'storeId' => $model->storeId,
                    'orderId' => $model->id
                ]);
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->select(['id', 'name']);
    }
    public function getStateFormatAttribute()
    {
        $data = [
            1 => "未支付",
            6 => "已完成"
        ];
        return $data[$this->state];
    }

    public function getOrderTypeFormatAttribute(){
        return "优惠券包";
    }

    public function activity()
    {
        return $this->hasOne(CouponPack::class, 'id', 'couponPackId')->select(['id', 'name'])->withTrashed();
    }
    public function couponPack()
    {
        return $this->hasOne(CouponPack::class, 'id', 'couponPackId')->select(['id', 'name'])->withTrashed();
    }

    public function getRefundFormatAttribute()
    {
        if ($this->state == 7 ||  $this->state == 8) {
            $data = [
                7 => '待审批',
                8 => "已退款"
            ];
            return $data[$this->state];
        }
        if ($this->refundState == 2) {
            return '退款拒绝';
        }
        return '';
    }
}
