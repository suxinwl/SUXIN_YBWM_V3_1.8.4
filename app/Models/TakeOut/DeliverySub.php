<?php

namespace App\Models\TakeOut;

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

class DeliverySub extends BaseModel
{
    protected $guarded = [];

    protected $_discount;
    protected $appends = [
        'discounts'
    ];

    public function getRulesTypeAttribute()
    {
        return $this->discount->rulesType;
    }

    public function getRulesAttribute()
    {
        return $this->discount->rules;
    }


    public function getTypeAttribute()
    {
        return $this->discount->type;
    }

    public function getDiscountsAttribute()
    {
        if (!$this->_discount) {
            $rule = null;
            if ($this->type == 3) {
                if ($this->rulesType == 1) {
                    foreach ($this->rules as $key => $v) {
                        if ($v['full'] <= $this->money) {
                            if ($v['free'] == 2) {
                                $rule = ['goodsMoney' => $this->money, 'full' => 0, 'sub' => $this->deliveryMoney];
                            } else {
                                $sub = $this->deliveryMoney < $v['sub'] ? $this->deliveryMoney : $v['sub'];
                                $rule = ['goodsMoney' => $this->money, 'full' => $v['full'], 'sub' => $sub];
                            }
                        }
                    }
                }
                if ($this->rulesType == 2) {
                    $rule = ['goodsMoney' => $this->money, 'full' => 0, 'sub' => $this->deliveryMoney];
                }
            }
            $this->_discount =  $rule;
        }
        return $this->_discount;
    }
}
