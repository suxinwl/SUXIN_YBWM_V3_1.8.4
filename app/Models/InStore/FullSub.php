<?php

namespace App\Models\InStore;

use App\Models\BaseModel;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Store as DeliveryStore;
use App\Models\Material;
use App\Models\Member\Address;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class FullSub extends BaseModel
{
    protected $guarded = [];

    protected $_discount;
    protected $_goodsMoney;
    protected $_money;
    protected $appends = [
        'discounts'
    ];
    public function getGoodsMoneyAttribute()
    {
        if (empty($this->_goodsMoney)) {
            $discount = $this->discount;
            if ($this->discount->goodsType == 1) {
                $this->_goodsMoney =  collect($this->goods)->sum('money');
            }
            if ($this->discount->goodsType == 2) {
                $this->_goodsMoney =   collect($this->goods)->filter(function ($goods, $key) use ($discount) {
                    return  in_array($goods->spuId, $discount->goodsIds);
                })->sum('money');
            }
            if ($this->discount->goodsType == 3) {
                $this->_goodsMoney =   collect($this->goods)->filter(function ($goods, $key) use ($discount) {
                    return  !in_array($goods->spuId, $discount->goodsIds);
                })->sum('money');
            }
        }
        return round($this->_goodsMoney,2);
    }

    public function getMoneyAttribute()
    {
        if (empty($this->_money)) {
            $discount = $this->discount;
            $this->_money = $this->goodsMoney;
            if (!in_array(1, $discount->threshold ?? [0])) {
                $this->_money = bcsub($this->_money, $this->materialMoney);
            }
            if (in_array(2, $discount->threshold ?? [0])) {
                $this->_money = bcadd($this->_money, $this->boxMoney);
            }
            if (in_array(3, $discount->threshold ?? [0])) {
                $this->_money = bcadd($this->_money, $this->deliveryMoney);
            }
        }
        return $this->_money;
    }

    public function getRulesTypeAttribute()
    {
        return $this->discount->rulesType;
    }

    public function getTypeAttribute()
    {
        return $this->discount->type;
    }

    public function getRulesAttribute()
    {
        return $this->discount->rules;
    }

    public function getDiscountsAttribute()
    {
        if (!$this->_discount) {
            $rule = null;
            if ($this->type == 1) {
                if ($this->rulesType == 1) {
                    foreach ($this->rules as $key => $v) {
                        if ($v['full'] < $this->money) {
                            $sub = $this->goodsMoney < $v['sub'] ? $this->goodsMoney : $v['sub'];
                            $rule = ['goodsMoney' => $this->money, 'full' => $v['full'], 'sub' => $sub];
                        }
                    }
                }
                if ($this->rulesType == 2) {
                    $ci = intval(bcdiv($this->goodsMoney, $this->rules['full']));
                    if ($ci > 0) {
                        $sub = $this->rules['sub'] * $ci;
                        $sub = $sub > $this->rules['max'] ? $this->rules['max'] : $sub;
                        $rule = ['goodsMoney' => $this->money, 'full' => $this->rules['full'], 'sub' => $sub];
                    }
                }
            }
            if ($this->type == 2) {
                foreach ($this->rules as $key => $v) {
                    if ($v['full'] < $this->money) {
                        $sub =  bcsub($this->goodsMoney, bcmul($this->goodsMoney, 0.1*$v['discount'], 2), 2);
                        $rule = ['goodsMoney' => $this->money, 'full' => $v['full'], 'sub' => $sub];
                    }
                }
            }
            $this->_discount =  $rule;
        }
        return $this->_discount;
    }
}
