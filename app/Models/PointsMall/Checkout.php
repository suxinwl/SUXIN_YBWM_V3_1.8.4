<?php

namespace App\Models\PointsMall;

use App\Models\BaseModel;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\PayConfig;
use App\Models\PointsMall;
use App\Models\Store;
use App\Models\Store\StoreBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Checkout extends BaseModel
{
    public $_order;
    protected $fillable = [
        'addressId', 'storeId', 'diningType', 'goodsId', 'uniacid', 'userId', 'channel', 'score', 'isolateStore'
    ];
    use HasFactory;
    protected $appends = [
        'order', 'diningType', 'pay', 'store'
    ];
    public function goods()
    {
        return $this->hasOne(PointsMall::class, 'id', 'goodsId')->where('uniacid', $this->uniacid);
    }


    public function getStoreAttribute()
    {
        return Store::find($this->storeId);
    }
    public function getAddressAttribute()
    {
        if ($this->diningType != 1) {
            return null;
        }
        if ($this->attributes['addressId']) {
            return Address::where('uniacid', $this->uniacid)->where('userId', $this->userId)->find($this->attributes['addressId']);
        }
        return Address::where('uniacid', $this->uniacid)->where('userId', $this->userId)->orderBy('id', 'desc')->first();
    }

    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }

    public function getOrderAttribute()
    {
        if (!$this->_order) {
            $this->_order =  new Order([
                'uniacid' => $this->uniacid,
                'userId' => $this->userId,
                'storeId' => $this->storeId ?? 0,
                'address' => $this->diningType == 1 ? $this->address : null,
                'orderSn' => getTakeOutNo(),
                'points' => $this->goods->integral,
                'money' => bcadd($this->goods->money, $this->deliveryMoney, 2),
                'scene' => 6,
                'goodsId' => $this->goodsId,
                'goods' => $this->goods,
                'diningType' => $this->diningType,
                'score' => $this->score,
                'state' => 1,
                'deliveryMoney' => $this->deliveryMoney,
            ]);
        }
        return $this->_order;
    }

    public function getDeliveryMoneyAttribute()
    {
        if ($this->diningType == 1) {
            return $this->goods->delivery_fee;
        }
        return 0;
    }

    public function getDiningTypeAttribute()
    {
        if ($this->attributes['diningType']) {
            return $this->attributes['diningType'];
        }
        if ($this->goods->delivery_switch && $this->goods->deliveryChannel) {
            return $this->goods->deliveryChannel[0];
        }
        return 0;
    }

    public function check()
    {
        if (empty($this->goods)) {
            throw new BadRequestException('兑换商品不存在');
        }
        if (($this->goods->stock) <= 0) {
            throw new BadRequestException('兑换商品库存不足');
        }
        if ($this->goods->integral > $this->user->account->integral) {
            throw new BadRequestException('积分不足，无法兑换该商品');
        }
    }

    public function getPayAttribute()
    {
        $channel = $this->channel;
        $score = $this->score;
        $list = PayConfig::where(function ($q) use ($channel, $score) {
            if (in_array($score, [3, 12])) {
                return $q->whereIn('payType', ["alipay", "balance"]);
            } elseif (in_array($score, [1, 2])) {
                return $q->whereIn('payType', ["weixin", "balance"]);
            } else {
                return $q->where('channel', $channel);
            }
        })
            ->where('uniacid', $this->uniacid)
            ->where('state', 1)
            ->where('storeId', $this->isolateStore)
            ->get();
        foreach ($list as $key => $v) {
            $isDefault = $v->isDefault;
            $v->setAppends([]);
            if ($v->payType == 'balance') {
                $v->balance = $this->user->account->balance;
            }
            $list[$key] = $v;
        }
        if ($isDefault == 0) {
            $list = collect($list)->map(function ($item, $key) {
                $item = $item->toArray();
                if ($key == 0) {
                    $item['isDefault'] = 1;
                }
                return $item;
            });
        }
        return [
            'expiredTime' => '',
            'expirationMinute' =>  '',
            'orderId' => '',
            'orderType' => '',
            'orderSn' => $this->order->orderSn,
            'payList' => $list,
            'money' => $this->order->money
        ];
    }
}
