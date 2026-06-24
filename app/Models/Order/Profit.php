<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use App\Models\InStore\Order\Order;
use App\Models\PayTemplate;
use App\Models\PersionPayOrder;
use App\Models\Store;
use App\Models\Store\Account;
use App\Models\StoredValue;
use App\Models\StoredValueOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use WeChatPay\Crypto\Rsa;

class Profit extends Bill
{
    use HasFactory;
    public $_subOrder;
    protected $table = 'order_bill';
    protected $appends = [
        'sharingStateFormat', 'orderTypeFormat'
    ];
    protected $casts =  [
        'rateConfig' => 'array',
        'sharingData' => 'array'
    ];
    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->select(['id', 'name']);
    }
    public function account()
    {
        return $this->hasOne(Account::class, 'storeId', 'storeId');
    }



    public function getOrderTypeFormatAttribute()
    {
        $data = [
            1 => $this->subOrder->diningTypeFormat,
            2 => "储值订单",
            3 => '买单订单',
            4 => '店内订单'
        ];
        return $data[$this->type];
    }

    public function getSubOrderAttribute()
    {
        if (empty($this->_subOrder)) {
            switch ($this->type) {
                case 1:
                    $this->_subOrder = TakeOutOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 2:
                    $this->_subOrder = StoredValueOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 3:
                    $this->_subOrder = PersionPayOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 4:
                    $this->_subOrder = Order::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
            }
        }
        return $this->_subOrder;
    }


    public function getwxMoneyAttribute()
    {
        if ($this->sharingData) {
            return  bcdiv(collect($this->sharingData['receivers'])->sum('amount'), 2, 100);
        }
        return "-";
    }

    public function getProfitAttribute()
    {
        return $this->subOrder->orderIndex->payTemplate->data['receivers'] ?? '-';
    }

    public function getStoreMoneyAttribute()
    {
        if ($this->sharingData) {
            return  bcdiv(collect($this->sharingData['receivers'])->where('account', $this->sharingData['sub_mchid'])->sum('amount'), 2, 100);
        }
        return "-";
    }
}
