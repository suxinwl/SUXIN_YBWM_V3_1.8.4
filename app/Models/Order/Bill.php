<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use App\Models\PayTemplate;
use App\Models\Store;
use App\Models\Store\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use WeChatPay\Crypto\Rsa;

class Bill extends BaseModel
{
    use HasFactory;
    protected $table = 'order_bill';
    protected $fillable = [
        'type',
        'uniacid',
        'orderSn',
        'refundMoney',
        'orderMoney',
        'payChannel',
        'payType',
        'storeId',
        'userId',
        'rateConfig',
        'goodsMoney',
        'boxMoney',
        'deliveryMoney',
        'serverMoney',
        'storeMoney',
        'serverGoodsMoney',
        'storeGoodsMoney',
        'serverBoxMoney',
        'storeBoxMoney',
        'serverDeliveryMoney',
        'storeDeliveryMoney',
        'payTempId',
        'profit_sharing',
        'sharingSn',
        'sharingState',
        'sharingData',
        'sharingTransaction_id',
        'msg',
        'sharingMoney',
        'thirdNo',
        'sharingMoney',
        'sharingSn',
        'mchId'
    ];
    protected $hidden = [
        'account', 'rateConfig'
    ];
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

    public function init()
    {
        $this->rateConfig = $this->account->rateConfig;
        if (in_array($this->type, [1])) {
            $this->getGoodsMoney();
            $this->getBoxMoney();
            $this->getDeliveryMoney();
            $this->serverMoney = bcadd(bcadd($this->serverGoodsMoney, $this->serverBoxMoney, 2), $this->serverDeliveryMoney, 2);
            $this->storeMoney = bcadd(bcadd($this->storeGoodsMoney, $this->storeBoxMoney, 2), $this->storeDeliveryMoney, 2);
        } elseif (in_array($this->type, [2, 6, 7, 8])) {
            $this->serverMoney = $this->orderMoney;
            $this->storeMoney = 0;
            $this->storeId = 0;
            return;
        } elseif ($this->type == 3) {
            $this->getPersoinPayMoney();
        } elseif ($this->type == 4) {
            $this->getInStoreMoney();
        } elseif ($this->type == 5) {
            $this->getPointsMailMoney();
        } elseif ($this->type == 6) {
            $this->getCouponPackMoney();
        }
        return;
    }

    public function getSharingStateFormatAttribute()
    {
        $data = [
            0 => '等待分账',
            1 => '分账成功',
            2 => "分账失败",
            3 => '正在分账中'
        ];
        return $data[$this->sharingState];
    }


    public function getOrderTypeFormatAttribute()
    {
        $data = [
            1 => '自提/外卖',
            2 => "储值订单",
            3 => '买单订单',
            4 => '店内订单'
        ];
        return $data[$this->type];
    }


    public function getGoodsMoney()
    {
        $config = $this->rateConfig['goods'];
        if ($config['check'] == "scale") {
            $goodsMoney =  bcmul(bcdiv($this->goodsMoney, 100, 5), $config['scale']['serverRate'], 2) ?? 0;
            if ($goodsMoney < $config['scale']['minMoney']) {
                $goodsMoney = $config['scale']['minMoney'];
            }
            $storeGoodsMoney = bcsub($this->goodsMoney, $goodsMoney, 2);
        }
        if ($config['check'] == "fixed") {
            $goodsMoney =  $config['fixed']['money'];
            $storeGoodsMoney = bcsub($this->goodsMoney, $goodsMoney, 2);
        }
        $this->serverGoodsMoney = $goodsMoney ?? 0;
        $this->storeGoodsMoney =  bcsub($this->goodsMoney, $this->serverGoodsMoney, 2);
        return true;
    }

    public function getBoxMoney()
    {
        $config = $this->rateConfig['box'];
        if ($config['check'] == "scale") {
            $goodsMoney =  bcmul(bcdiv($this->boxMoney, 100, 5), intval($config['scale']['serverRate']), 2) ?? 0;
            if ($goodsMoney < $config['scale']['minMoney']) {
                $goodsMoney = $config['scale']['minMoney'] ?? 0;
            }
            $storeGoodsMoney = bcsub($this->boxMoney, $goodsMoney, 2);
        }
        if ($config['check'] == "fixed") {
            $goodsMoney =  $config['fixed']['money'] ?? 0;
            $storeGoodsMoney = bcsub($this->boxMoney, $goodsMoney, 2);
        }
        $this->serverBoxMoney = $goodsMoney ?? 0;
        $this->storeBoxMoney = bcsub($this->boxMoney, $this->serverBoxMoney, 2);
        return true;
    }

    public function getDeliveryMoney()
    {
        $config = $this->rateConfig['delivery'];
        if ($config['check'] == "scale") {
            $goodsMoney =  bcmul(bcdiv($this->deliveryMoney, 100, 5), $config['scale']['serverRate'], 2) ?? 0;
            if ($goodsMoney < $config['scale']['minMoney']) {
                $goodsMoney = $config['scale']['minMoney'] ?? 0;
            }
            $storeGoodsMoney = bcsub($this->deliveryMoney, $goodsMoney, 2);
        }
        if ($config['check'] == "fixed") {
            $goodsMoney =  $config['fixed']['money'];
            $storeGoodsMoney = bcsub($this->deliveryMoney, $goodsMoney, 2);
        }
        $this->serverDeliveryMoney = $goodsMoney ?? 0;
        $this->storeDeliveryMoney = bcsub($this->deliveryMoney, $this->serverDeliveryMoney, 2);
        return true;
    }

    public function getPersoinPayMoney()
    {
        $config = $this->rateConfig['persionPay'];
        if ($config['check'] == "scale") {
            $goodsMoney =  bcmul(bcdiv($this->orderMoney, 100, 5), $config['scale']['serverRate'], 2) ?? 0;
            if ($goodsMoney < $config['scale']['minMoney']) {
                $goodsMoney = $config['scale']['minMoney'] ?? 0;
            }
            $storeGoodsMoney = bcsub($this->orderMoney, $goodsMoney, 2);
        }
        if ($config['check'] == "fixed") {
            $goodsMoney =  $config['fixed']['money'];
            $storeGoodsMoney = bcsub($this->orderMoney, $goodsMoney, 2);
        }
        $this->serverMoney = $goodsMoney ?? 0;
        $this->storeMoney =  bcsub($this->orderMoney, $this->serverMoney, 2);
        return true;
    }
    public function getInStoreMoney()
    {
        $config = $this->rateConfig['inStore'];
        if ($config['check'] == "scale") {
            $goodsMoney =  bcmul(bcdiv($this->orderMoney, 100, 5), $config['scale']['serverRate'], 2) ?? 0;
            if ($goodsMoney < $config['scale']['minMoney']) {
                $goodsMoney = $config['scale']['minMoney'] ?? 0;
            }
            $storeGoodsMoney = bcsub($this->orderMoney, $goodsMoney, 2);
        }
        if ($config['check'] == "fixed") {
            $goodsMoney =  $config['fixed']['money'];
            $storeGoodsMoney = bcsub($this->orderMoney, $goodsMoney, 2);
        }
        $this->serverMoney = $goodsMoney ?? 0;
        $this->storeMoney =  bcsub($this->orderMoney, $this->serverMoney, 2);
        return true;
    }

    public function getPointsMailMoney()
    {
        $this->serverMoney = 0;
        $this->storeMoney =  bcsub($this->orderMoney, $this->serverMoney, 2);
    }

    public function getCouponPackMoney()
    {
        $this->serverMoney = 0;
        $this->storeMoney =  bcsub($this->orderMoney, $this->serverMoney, 2);
    }
}
